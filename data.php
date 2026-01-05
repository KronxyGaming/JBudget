<?php
    $DBConnect = mysqli_connect("localhost", "root", "", "jbudget");
	if($DBConnect == false) {
		die("Unable to connect to the database: " . mysqli_connect_errno());
	}

    session_start();
    $user_id = $_SESSION['user_id'] ?? null; // Use null coalescing operator to avoid undefined index notice

    $ip = $_SERVER['REMOTE_ADDR']; // Get the user's IP address
    if($ip === '127.0.0.1' || $ip === '::1') {
        $ip = '104.251.67.184'; // if local address is detected, replace with actual IP
    }
    $ip_api_url = "http://ip-api.com/json/{$ip}?fields=status,message,country,countryCode,region,regionName,city,zip,lat,lon,timezone,isp,org,as,query"; // api url to retrieve IP information
    $api_response = file_get_contents($ip_api_url); // Fetch the IP information from the API
    $ip_data = json_decode($api_response, true); // Decode the JSON response into an associative array

    if($ip_data['status'] === 'success') { // Check if the API call was successful
        // Extract the relevant information from the response
        $country = $ip_data['country'];
        $countryCode = $ip_data['countryCode'];
        $city = $ip_data['city'];
        $zip = $ip_data['zip'];
        $timezone = $ip_data['timezone'];
        $isp = $ip_data['isp'];
        $as = $ip_data['as'];
        $query = $ip_data['query'];
        $status = $ip_data['status'];
        $regionName = $ip_data['regionName'];

        $SQLstring = "INSERT INTO data(user_id, country, countryCode, regionName, city, zip, timezone, isp, `as`, `query`, `status`)
						VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($DBConnect, $SQLstring);
        if($stmt) {
            mysqli_stmt_bind_param($stmt, "issssssssss", $user_id, $country, $countryCode, $regionName, $city, $zip, $timezone, $isp, $as, $query, $status);
            if(mysqli_stmt_execute($stmt)) {
                print "IP stored created successfully<br>";
            } else {
                print "Error on insert: " . mysqli_errno($DBConnect);
            }
            mysqli_stmt_close($stmt);
        } else {
            die("Prepare failed: " . mysqli_error($DBConnect));
        }
        
    } else {
        echo "Failed to retrieve IP information: " . $ip_data['message'] . "<br>";
    }
?>
