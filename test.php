<!DOCTYPE html>
<html>
<head>
    <title>Previous Search Phrase</title>
</head>
<body>

    <h1>Previous Search Phrase</h1>

    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
        <label for="urls">Enter URLs (one per line):</label><br>
        <textarea id="urls" name="urls" rows="10" cols="50"></textarea><br>
        <label for="search">Search Query:</label>
        <input type="text" id="search" name="search"><br>
        <input type="submit" name="submit" value="Submit">
    </form>

<?php
// Set error reporting level to display all errors, warnings and notices
error_reporting(E_ALL);    

// Include the Simple HTML DOM Parser library
require_once('simple_html_dom.php');

$search_query = isset($_POST['search']) ? $_POST['search'] : '';
$results = array();
$html_output = '';
$search_query_slug = strtolower(str_replace(' ', '_', $search_query));
$output_dir_name = './' . $search_query_slug;


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Add a delay of 1 second between each cURL request to avoid being blocked by Amazon
    usleep(1000000); // Delay for 1 second (in microseconds) 

    $urls = array_map('trim', explode("\n", $_POST['urls']));

    $external_link_pattern = '/^https?:\/\/(www\.)?(amazon\.com\/(.*\/)?dp\/[A-Z0-9]{10}|amzn\.to\/[A-Za-z0-9]+)/i';

    // Loop through the URLs and scan for Amazon product pages
    foreach ($urls as $url) {
        try {
            // Load the HTML code for the URL using the Simple HTML DOM Parser library
            $html = file_get_html($url);
            if (!$html) {
                throw new Exception("Error loading HTML code for URL: $url");
            }

            // Extract the Amazon product pages from the HTML code and store them in an array
            $product_pages = array();
            foreach ($html->find('a') as $a) {
                $href = $a->href;
                if (preg_match($external_link_pattern, $href, $matches)) {
                    if (strpos($matches[0], 'amzn.to') !== false) { // Check if it's a short URL
                        $long_url = file_get_contents('https://unshorten.me/s/' . $matches[0]); // Get the long URL
                        if ($long_url) { // Check if the long URL was retrieved successfully
                            $product_pages[] = $long_url;
                        }
                    } else {
                        $product_pages[] = $matches[0];
                    }
                }
            }

            // Count unique instances of Amazon product pages
            $unique_product_pages = array_count_values($product_pages);

            // Add the Amazon product pages to the results array
            $result = array('url' => $url, 'products' => array());
            foreach ($unique_product_pages as $page => $frequency) {
                // Extract the Amazon product page code (ASIN) from the URL
                if (preg_match('/\/([A-Z0-9]{10})(\/|\?|$)/', $page, $matches)) {
                    $code = $matches[1];
                } else {
                    $code = '';
                }

                // Add the product name, ASIN code, and link to the result
                if (preg_match('/amazon\.com\/([^\/]+)\/dp\/([A-Z0-9]{10})(\/|\?|$)/', $page, $matches)) {
                    $product_name = str_replace('-', ' ', $matches[1]);
                    $code = $matches[2];
                    $link = $page;
                } else {
                    $product_name = '';
                    $link = '';
                }
                $result['products'][] = array('name' => $product_name, 'code' => $code, 'link' => $link, 'frequency' => $frequency);
            }

            $results[] = $result;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
}

   // Convert the results array to the required format for JSON file
$json_output = array();
foreach ($results as $result) {
    $json_item = array(
        'url' => $result['url'],
        'products' => array()
    );
    foreach ($result['products'] as $product) {
        $json_item['products'][] = array(
            'name' => $product['name'],
            'code' => $product['code']
        );
    }
    $json_output[] = $json_item;
}


// Generate HTML output
$html_output = '';
foreach ($results as $result) {
    $html_output .= "<h3>{$result['url']}</h3>";
    $html_output .= "<table>";
    $html_output .= "<tr><th>Product</th><th>ASIN CODE</th><th>Frequency</th><th>Link</th></tr>";
    foreach ($result['products'] as $product) {
        $html_output .= "<tr><td>{$product['name']}</td><td>{$product['code']}</td><td>{$product['frequency']}</td><td><a href='{$product['link']}' target='_blank'>Visit</a></td></tr>";
    }
    $html_output .= "</table>";
}

if (isset($_POST['search'])) {
    // Create a new directory for the search query
    $search_query = isset($_POST['search']) ? $_POST['search'] : '';
    $search_query_slug = strtolower(str_replace(' ', '_', $search_query));
    $search_query_dir = "./{$search_query_slug}";
    if (!file_exists($search_query_dir)) {
        mkdir($search_query_dir);
        chmod($search_query_dir, 0777); // Add this line to set permissions
    }

    // Copy the necessary files to the new directory
    copy('./sort_and_update_json.php', $search_query_dir . '/sort_and_update_json.php');
    chmod($search_query_dir . '/sort_and_update_json.php', 0777); // set permission to 0777 for the file

    copy('./simple_html_dom.php', $search_query_dir . '/simple_html_dom.php');
    chmod($search_query_dir . '/simple_html_dom.php', 0777); // set permission to 0777 for the file

    copy('./update_sorted_data.php', $search_query_dir . '/update_sorted_data.php');
    chmod($search_query_dir . '/update_sorted_data.php', 0777); // set permission to 0777 for the file

    copy('./extractimages_createlisting.php', $search_query_dir . '/extractimages_createlisting.php');
    chmod($search_query_dir . '/extractimages_createlisting.php', 0777); // set permission to 0777 for the file


    // Write the results to an HTML file
    $html_filename = !empty($search_query) ? "$search_query_slug.html" : 'output.html';
    $html_filepath = $search_query_dir . '/' . $html_filename;
    if (!empty($html_filepath) && file_put_contents($html_filepath, $html_output)) {
        echo "HTML output: <a href='$html_filepath'>$html_filename</a><br>";
    }

    // Set the output file path for the JSON file
    $json_filename = !empty($search_query) ? "$search_query_slug.json" : 'output.json';
    $json_filepath = $search_query_dir . '/' . $json_filename;

    // Write the results to a JSON file
    if (!empty($json_filepath) && @file_put_contents($json_filepath, json_encode($json_output, JSON_PRETTY_PRINT))) {
        echo "Output written to $search_query_dir<br>";
        echo "JSON output: <a href='$json_filepath'>$json_filename</a><br>";
    }

    // Create link to first copied file
    if (!empty($search_query_dir)) {
        $first_file = $search_query_dir . '/sort_and_update_json.php';
        if (file_exists($first_file)) {
            echo "First copied file: <a href='$first_file'>$first_file</a><br>";
        }
    }
}
?>