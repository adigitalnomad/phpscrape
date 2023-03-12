<?php

if ($argc < 2) {
    exit("Please provide the path to the sorted JSON file as a parameter.\n");
}

$file_path = $argv[1];

// Check if the file exists
if (!file_exists($file_path)) {
    exit("The file does not exist.\n");
}

// Load the JSON file contents into a variable
$json_string = file_get_contents($file_path);

// Decode the JSON string into an array
$sorted_results = json_decode($json_string, true);

// Loop through the sorted products and make a call to the ASIN Data API for each product
foreach ($sorted_results['products'] as &$product) {
    // Set up the request parameters for the ASIN Data API
    $query_string = http_build_query([
        'api_key' => '2233BBA09DBD4188B51CBB31F3836E46',
        'amazon_domain' => 'amazon.com',
        'asin' => $product['code'],
        'type' => 'product',
        'output' => 'json',
        'include_images' => 'true',
        'include_attributes' => 'true',
        'include_specifications' => 'true',
        'include_feature_bullets' => 'true'
    ]);

    // Make the http GET request to ASIN Data API
    $ch = curl_init(sprintf('%s?%s', 'https://api.asindataapi.com/request', $query_string));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    // The following options are required if you're using an outdated OpenSSL version
    // More details: https://www.openssl.org/blog/blog/2021/09/13/LetsEncryptRootCertExpire/
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 180);

    $api_result = curl_exec($ch);
    curl_close($ch);

    // Decode the API response JSON string into an array
    $api_data = json_decode($api_result, true);

    // Extract the required data from the API response and update the product
    $product['title'] = $api_data['title'];
    $product['images'] = explode(',', $api_data['images_flat']);
    $product['feature_bullets'] = explode('. ', $api_data['feature_bullets_flat']);
    $product['attributes'] = $api_data['attributes'];
    $product['specifications'] = explode('. ', $api_data['specifications_flat']);
}

// Save the updated sorted products to a file
$output_file_path = str_replace('.json', '_updated.json', $file_path);
file_put_contents($output_file_path, json_encode($sorted_results, JSON_PRETTY_PRINT));

// Output a message to indicate the script is finished
echo "Done!\n";

?>





//call the newly sorted json file: 



//establish connection


//pass reuest to the API



//retireive the addtional attruinutes per CODE



//append the addtional atributes per code


//Prepare to weite new json file



//Count the amount of URL in flat_images and create image folder per product name



//fetch and deposit the images into folder with "01 - Product Name", 



//call next script: create_product_listing