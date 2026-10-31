<?php

return [
    'python_binary' => env('IDENTITY_PYTHON_BINARY', 'python'),
    'tesseract_binary' => env('TESSERACT_BINARY', 'tesseract'),
    'yunet_model' => env(
        'IDENTITY_YUNET_MODEL',
        storage_path('app/private/identity-models/face_detection_yunet_2023mar.onnx')
    ),
    'sface_model' => env(
        'IDENTITY_SFACE_MODEL',
        storage_path('app/private/identity-models/face_recognition_sface_2021dec.onnx')
    ),
    'face_similarity_threshold' => 0.363,
    'face_detector_confidence_threshold' => 0.75,
];
