<?php

$json_data = file_get_contents('data.json');
$data = json_decode($json_data, true);

$product_dir = "Products";
if (!is_dir($product_dir)) {
    mkdir($product_dir);
}

foreach ($data['products'] as $product) {
    $product_dir = "Products/" . $product['title'] . " - " . $product['code'];
    if (!is_dir($product_dir)) {
        mkdir($product_dir);
    }

    $image_urls = explode(',', $product['images_flat']);
    foreach ($image_urls as $key => $url) {
        $url = trim($url);
        if (empty($url)) {
            continue;
        }
        $image_filename = $product['title'] . " - " . $product['code'] . " - " . ($key + 1) . ".jpg";
        $image_path = $product_dir . "/" . $image_filename;
        file_put_contents($image_path, file_get_contents($url));
    }

    $total_images = count($image_urls);
    $product_title = htmlspecialchars($product['title']);
    $product_listing_html = "<!DOCTYPE html>\n<html>\n<head>\n<title>$product_title</title>\n</head>\n<body>\n<h1>$product_title</h1>\n<p>Total Images: $total_images</p>\n<div>";

    foreach ($image_urls as $url) {
        $url = trim($url);
        if (empty($url)) {
            continue;
        }
        $product_listing_html .= "<img src='$url' alt='$product_title'>";
    }

    $product_listing_html .= "</div>\n</body>\n</html>";
    file_put_contents($product_dir . "/product-listing.html", $product_listing_html);

    echo "Images saved to " . $product_dir . " directory. Product listing HTML file created.\n";
}

?>
