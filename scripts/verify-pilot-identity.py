import json
import re
import sys
import unicodedata
from pathlib import Path


def normalize(value):
    value = unicodedata.normalize("NFKD", value)
    value = value.encode("ascii", "ignore").decode("ascii").upper()
    return re.sub(r"[^A-Z0-9]+", " ", value).strip()


def pending(reason):
    return {"status": "pending_review", "reason": reason}


def extract_ocr_tokens(ocr_data):
    tokens = []
    for index, (text, confidence) in enumerate(zip(ocr_data["text"], ocr_data["conf"])):
        token = normalize(text)
        try:
            confidence_value = float(confidence)
        except (TypeError, ValueError):
            continue
        if token and confidence_value >= 40:
            tokens.extend(token.split())

    return tokens


def document_number_matches(document_number, recognized_texts):
    document_pattern = r"(?<![A-Z0-9])" + r"\s*".join(
        re.escape(char) for char in document_number
    ) + r"(?![A-Z0-9])"
    return any(re.search(document_pattern, text) for text in recognized_texts)


def aligned_face(candidate, detector, recognizer, cv2, confidence_threshold):
    height, width = candidate.shape[:2]
    maximum_scale = min(3.0, (20000000 / (width * height)) ** 0.5)
    grayscale = cv2.cvtColor(candidate, cv2.COLOR_BGR2GRAY)
    clahe = cv2.createCLAHE(clipLimit=2.0, tileGridSize=(8, 8))
    enhanced = cv2.cvtColor(
        clahe.apply(grayscale),
        cv2.COLOR_GRAY2BGR,
    )
    last_failure = "not_detected"

    for image in (candidate, enhanced):
        scale = 1.0
        while True:
            detection_image = image
            if scale > 1:
                detection_image = cv2.resize(
                    image,
                    None,
                    fx=scale,
                    fy=scale,
                    interpolation=cv2.INTER_CUBIC,
                )

            detection_height, detection_width = detection_image.shape[:2]
            detector.setInputSize((detection_width, detection_height))
            _, faces = detector.detect(detection_image)
            if faces is not None and len(faces) > 1:
                return None, "multiple_faces"
            if faces is not None and len(faces) == 1:
                face = faces[0]
                if face[2] / scale < 40 or face[3] / scale < 40:
                    last_failure = "face_too_small"
                elif face[14] < confidence_threshold:
                    last_failure = "low_confidence"
                else:
                    return recognizer.feature(
                        recognizer.alignCrop(detection_image, face)
                    ), None

            next_scale = next(
                (
                    candidate_scale
                    for candidate_scale in (1.5, 2.0, 3.0)
                    if candidate_scale > scale
                    and candidate_scale <= maximum_scale
                ),
                None,
            )
            if next_scale is None:
                break
            scale = next_scale

    return None, last_failure


def verify(payload, document_front_path, document_back_path, selfie_path, tesseract_binary, yunet_path, sface_path):
    try:
        import cv2
        import pytesseract
        from pytesseract import Output
    except ImportError:
        return pending("No están instaladas las dependencias locales de OCR y comparación facial.")

    if not Path(yunet_path).is_file() or not Path(sface_path).is_file():
        return pending("No están instalados los modelos locales de detección y comparación facial.")

    pytesseract.pytesseract.tesseract_cmd = tesseract_binary

    try:
        document_front = cv2.imread(document_front_path)
        document_back = cv2.imread(document_back_path)
        selfie = cv2.imread(selfie_path)
        if document_front is None or document_back is None or selfie is None:
            return pending("Una o más imágenes no se pudieron decodificar.")

        for candidate in (document_front, document_back, selfie):
            height, width = candidate.shape[:2]
            if width < 100 or height < 100 or width * height > 20000000:
                return pending("Una o más imágenes tienen dimensiones inadecuadas para la revisión automática.")

        recognized_document_texts = []
        for candidate in (document_front, document_back):
            ocr_data = pytesseract.image_to_data(
                cv2.cvtColor(candidate, cv2.COLOR_BGR2RGB),
                lang="spa+eng",
                config="--oem 1 --psm 6",
                output_type=Output.DICT,
            )
            tokens = extract_ocr_tokens(ocr_data)
            recognized_document_texts.append(" ".join(tokens))

        document_number = normalize(payload["identity_document_number"]).replace(" ", "")
        if not document_number:
            return pending("El número de carnet ingresado no es válido para la revisión automática.")

        if not document_number_matches(document_number, recognized_document_texts):
            for candidate in (document_front, document_back):
                grayscale = cv2.cvtColor(candidate, cv2.COLOR_BGR2GRAY)
                height, width = grayscale.shape[:2]
                scale = min(2.0, (20000000 / (width * height)) ** 0.5)
                enlarged = cv2.resize(
                    grayscale,
                    None,
                    fx=scale,
                    fy=scale,
                    interpolation=cv2.INTER_CUBIC,
                )
                retry_data = pytesseract.image_to_data(
                    enlarged,
                    lang="spa+eng",
                    config="--oem 1 --psm 11",
                    output_type=Output.DICT,
                )
                tokens = extract_ocr_tokens(retry_data)
                recognized_document_texts.append(" ".join(tokens))

        if not document_number_matches(document_number, recognized_document_texts):
            return pending(
                "No se pudo confirmar el número ingresado. Verifica que coincida con el carnet "
                "y vuelve a subir fotos nítidas, completas y sin reflejos de ambas caras."
            )

        detector = cv2.FaceDetectorYN.create(
            yunet_path,
            "",
            (320, 320),
            0.5,
            0.3,
            5000,
        )
        recognizer = cv2.FaceRecognizerSF.create(sface_path, "")
        confidence_threshold = float(payload["face_detector_confidence_threshold"])
        document_feature, document_failure = aligned_face(
            document_front,
            detector,
            recognizer,
            cv2,
            confidence_threshold,
        )
        if document_feature is None:
            return pending(face_failure_reason("el anverso del carnet", document_failure))

        selfie_feature, selfie_failure = aligned_face(
            selfie,
            detector,
            recognizer,
            cv2,
            confidence_threshold,
        )
        if selfie_feature is None:
            return pending(face_failure_reason("la selfie", selfie_failure))

        similarity = recognizer.match(
            document_feature,
            selfie_feature,
            cv2.FaceRecognizerSF_FR_COSINE,
        )
        if similarity < float(payload["face_similarity_threshold"]):
            return pending("La comparación facial no alcanzó el umbral automático de coincidencia.")

        return {
            "status": "verified",
            "reason": "El OCR coincide con el carnet y la comparación SFace superó el umbral documentado.",
        }
    except (pytesseract.TesseractNotFoundError, pytesseract.TesseractError, cv2.error, OSError, ValueError) as error:
        print("Local identity verification failed: " + str(error), file=sys.stderr)
        return pending("No se pudo completar una comprobación automática. El administrador revisará tu solicitud.")


def face_failure_reason(image_name, failure):
    if failure == "multiple_faces":
        return "Se detectaron varios rostros en " + image_name + ". Sube una imagen donde solo aparezca el piloto."
    if failure == "face_too_small":
        return "El rostro de " + image_name + " aparece demasiado pequeño. Acerca y enfoca mejor la imagen."
    if failure == "low_confidence":
        return "El rostro de " + image_name + " no se pudo identificar con suficiente claridad. Mejora el enfoque y la iluminación."
    return "No se pudo detectar un rostro en " + image_name + ". Sube una imagen frontal, enfocada y con buena iluminación."


def main():
    if len(sys.argv) != 7:
        print(json.dumps(pending("La configuración local de validación automática está incompleta.")))
        return

    try:
        payload = json.load(sys.stdin)
        result = verify(payload, *sys.argv[1:])
    except (json.JSONDecodeError, KeyError, TypeError) as error:
        print("Invalid local identity verification input: " + str(error), file=sys.stderr)
        result = pending("No se pudo completar la revisión automática. El administrador revisará tu solicitud.")

    print(json.dumps(result, ensure_ascii=True))


if __name__ == "__main__":
    main()
