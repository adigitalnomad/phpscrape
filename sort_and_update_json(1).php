<?php
// Retrieve the search query from the directory name
$search_query = basename(__DIR__);

// Load the JSON data from the output.json file into a PHP array using file_get_contents() and json_decode()
$json_file = __DIR__ . "/{$search_query}.json";
$json_data = json_decode(file_get_contents($json_file), true);


// Load the JSON file contents into a variable
$json_string = file_get_contents($json_file);

// Decode the JSON string into an array
$json_data = json_decode($json_string, true);

// Calculate the frequency of each product across all sites
$results = $json_data;
$products_frequency = array();
foreach ($results as $result) {
    foreach ($result['products'] as $product) {
        if (isset($products_frequency[$product['code']])) {
            $products_frequency[$product['code']]++;
        } else {
            $products_frequency[$product['code']] = 1;
        }
    }
}

// Sort the products by frequency
uasort($products_frequency, function($a, $b) {
    return $b - $a;
});

// Loop through the sorted products and make a call to the ASIN Data API for each product
$sorted_products = array();
foreach ($products_frequency as $code => $frequency) {
    foreach ($results as $result) {
        foreach ($result['products'] as $product) {
            if ($product['code'] == $code) {
                // Set up the request parameters for the ASIN Data API
                $query_string = http_build_query([
                    'api_key' => '2233BBA09DBD4188B51CBB31F3836E46',
                    'amazon_domain' => 'amazon.com',
                    'asin' => $product['code'],
                    'type' => 'product',
                    'output' => 'csv',
                    'csv_fields' => 'product.title,product.rating,product.ratings_total,product.buybox_winner.availability.raw,product.title'
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
            

                // Process the CSV response and extract the required data
$sorted_products_temp = array();
$lines = explode("\n", $api_result);
foreach ($lines as $line) {
    $fields = str_getcsv($line);
    if (count($fields) >= 4) {
        $product_data = array(
            'name' => $product['name'],
            'code' => $product['code'],
            'title' => $fields[0],
            'rating' => $fields[1],
            'ratings_total' => $fields[2],
            'reviews_total' => '',
            'buybox_winner' => array(
                'availability' => array(
                    'raw' => $fields[3]
                )
            )
        );
        if ($product_data['buybox_winner']['availability']['raw'] == 'In stock') {
            $sorted_products_temp[] = $product_data;
        }
    }
}

// Sort the remaining products by frequency and ratings
    uasort($sorted_products_temp, function($a, $b) use ($products_frequency, $results) {
    // Compare the frequencies of the products
    $a_frequency = $products_frequency[$a['code']];
    $b_frequency = $products_frequency[$b['code']];
    if ($a_frequency == $b_frequency) {
        // If the frequencies are the same, compare the ratings of the associated products
        $a_total_ratings = 0;
        $a_total_rating = 0;
        $b_total_ratings = 0;
        $b_total_rating = 0;

        // Calculate the total ratings and rating for products associated with product A
        foreach ($results as $result) {
            foreach ($result['products'] as $product) {
                if ($product['code'] == $a['code']) {
                    $a_total_ratings += $product['ratings_total'];
                    $a_total_rating += $product['rating'];
                }
            }
        }

        // Calculate the total ratings and rating for products associated with product B
        foreach ($results as $result) {
            foreach ($result['products'] as $product) {
                if ($product['code'] == $b['code']) {
                    $b_total_ratings += $product['ratings_total'];
                    $b_total_rating += $product['rating'];
                }
            }
        }

        // Compare the ratings of the associated products
        if ($a_total_rating == $b_total_rating) {
            return $b_total_ratings - $a_total_ratings;
            } else {
                return $b_total_rating - $a_total_rating;
                    }
                } else {
                return $b_frequency - $a_frequency;
        }   
});

// Create the final sorted products array with the required format
$sorted_products = array(
    'search_term' => $search_query,
    'products' => $sorted_products_temp
);

// Save the sorted products to a file
$output_file_path = str_replace('.json', '_sorted.json', $json_file);
file_put_contents($output_file_path, json_encode($sorted_products, JSON_PRETTY_PRINT));

// Output a message to indicate the script is finished
echo "Done!\n";
