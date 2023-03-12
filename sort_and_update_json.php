<?php
$search_query = basename(__DIR__);
$json_file = __DIR__ . "/{$search_query}.json";
$json_data = json_decode(file_get_contents($json_file), true);
$json_string = file_get_contents($json_file);
$json_data = json_decode($json_string, true);
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
uasort($products_frequency, function($a, $b) {
    return $b - $a;
});
$sorted_products_temp = array();
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
        $lines = explode("\n", $api_result);
        foreach ($lines as $line) {
    $fields = str_getcsv($line);
    if (count($fields) >= 4) {
        $product['title'] = $fields[0];
        $product['rating'] = $fields[1];
        $product['ratings_total'] = $fields[2];
        $product['buybox_winner']['availability']['raw'] = $fields[3];
    } 
                        if ($product_data['buybox_winner']['availability']['raw'] == 'In stock') {
                            $sorted_products_temp[] = $product_data;
                        }
                    }
                }

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

                        foreach ($results as $result) {
    foreach ($result['products'] as $product) {
        if ($product['code'] == $a['code']) {
            $a_total_ratings += $product['ratings_total'];
            $a_total_rating += $product['rating'];
        }
    }
}
foreach ($results as $result) {
    foreach ($result['products'] as $product) {
        if ($product['code'] == $b['code']) {
            $b_total_ratings += $product['ratings_total'];
            $b_total_rating += $product['rating'];
        }
    }
}

if ($a_total_rating == $b_total_rating) {
    return $b_total_ratings - $a_total_ratings;
} else {
    return $b_total_rating - $a_total_rating;
}
} else {
    return $b_frequency - $a_frequency;
}
            });

            $sorted_products = array(
                'search_term' => $search_query,
                'products' => $sorted_products_temp
            );

            $output_file_path = str_replace('.json', '_sorted.json', $json_file);
            file_put_contents($output_file_path, json_encode($sorted_products, JSON_PRETTY_PRINT));
            echo "Done!\n";
        }
    }
}

