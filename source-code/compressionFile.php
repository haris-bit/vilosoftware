<?
$uploadsFolder = './uploads/';

foreach (glob($uploadsFolder . '*.{jpg,jpeg,png,webp}', GLOB_BRACE) as $imagePath) {
    $info = pathinfo($imagePath);
    $extension = strtolower($info['extension']);

    switch ($extension) {
        case 'jpg':
        case 'jpeg':
            $image = imagecreatefromjpeg($imagePath);
            $exif = exif_read_data($imagePath);
            if ($exif !== false && isset($exif['Orientation'])) {
                $orientation = $exif['Orientation'];
                switch ($orientation) {
                    case 3:
                        $image = imagerotate($image, 180, 0);
                        break;
                    case 6:
                        $image = imagerotate($image, -90, 0);
                        break;
                    case 8:
                        $image = imagerotate($image, 90, 0);
                        break;
                }
            }
            break;
        case 'png':
            continue;
        case 'webp':
            $image = imagecreatefromwebp($imagePath);
            break;
        default:
            continue; // Skip unsupported file types
    }
    
    // Adjust the quality parameter (0 to 100) based on your needs
    if ($extension === 'png') {
        //imagepng($image, $imagePath, 9); // Adjust 5 for compression level
    } else {
        imagejpeg($image, $imagePath, 15); // Adjust 50 for JPEG quality
    }

    imagedestroy($image);
}

?>