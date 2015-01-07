<?php
const KEEP_RUNNING = true;
const DEBUG_MODE = false;
const STORE_IN_DB = true;
const COUNTER_INIT = 10;

$host = 'localhost';
$username = 'root';
$pass = '';
$dbName = 'pemulung';
$tableName = 'url';

// check out the {counter} token. We use this to increase the page
$url = 'http://www.SOMEURL.com/search/index?page={counter}';
$whiteList = array(
    '-dijual-',
);
$blacklist = array(
    '/mortgage',
);
$append = '';



// TUrn on the the machine
$mysqli = new mysqli($host, $username, $pass, $dbName);
if($mysqli->connect_errno){
    echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
}

// Create table
$query = "Select * from $tableName";
$mysqli->query($query);
if(!$mysqli->query($query)){
    $query = "CREATE TABLE $tableName (
                    id int(11) AUTO_INCREMENT,
                    url varchar(255) NOT NULL,
                    hash varchar(64) NOT NULL,
                    PRIMARY KEY (`id`)
                )";
    $result = $mysqli->query($query);
    $result = $mysqli->query("ALTER TABLE $tableName ADD INDEX hash(hash)");
}

// Find some valuable items (mine)
$counter = COUNTER_INIT;
$doc = new DOMDocument;
do {
    $tps = str_replace("{counter}", $counter, $url);
    echo "Processing $tps ...".PHP_EOL;
    $html = file_get_contents($tps);
    libxml_use_internal_errors(true);   //suppress warning and error
    $flag = $doc->loadHTML($html);
    libxml_use_internal_errors(false);

    if($flag){
        $links = $doc->getElementsByTagName("a");
        foreach($links as $link){
            // Check white list
            foreach($whiteList as $needle){
                $sampah = trim($append . $link->getAttribute('href'));
                if(DEBUG_MODE){
                    echo $sampah.PHP_EOL;
                }
                $found = strpos($sampah, $needle);
                if($found){
                    // check blacklist
                    foreach($blacklist as $blackNeedle){
                        $exclude = strpos($sampah, $blackNeedle);
                        if(!$exclude){
                            if(STORE_IN_DB){
                                $hash = hash("sha256", $sampah);
                                // check duplicate in database
                                $dup = $mysqli->query("SELECT * FROM $tableName WHERE hash = '$hash'");
                                if($dup->num_rows == 0){
                                    $query = "INSERT INTO $tableName (url, hash) VALUES ('$sampah', '$hash')";
                                    $mysqli->query($query);
                                    break;
                                }
                            } else {
                                echo $sampah.PHP_EOL;
                            }
                        }
                    }

                }
            }
        }
    }
    $counter --;

    $rest = rand(1, 10);

    // Maintenance cycle
    if(KEEP_RUNNING && $counter == 0){
        $counter = 5;
        // sleep longer
        $rest = rand(100, 1000);
    }

    echo "Sleeping for $rest seconds... Good night!".PHP_EOL;
    sleep($rest);

} while ($counter > 0);
