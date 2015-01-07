<?php
const PAGINATION = 10;

$host = 'localhost';
$username = 'root';
$pass = '';
$dbName = 'pemulung';
$pageTable = 'page_table';
$urlTable = 'url_table';

//connect db
$mysqli = new mysqli($host, $username, $pass, $dbName);
if($mysqli->connect_errno){
    echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
    return;
}

if(!$mysqli->query("Select id from $urlTable limit 1")){
    echo "Failed to connect to table: $urlTable".PHP_EOL;
    return;
}

//create page table
$query = "Select id from $pageTable limit 1";
$mysqli->query($query);
if(!$mysqli->query($query)){
    $query = "CREATE TABLE $pageTable (
        id int(11) AUTO_INCREMENT,
        url varchar(255) NOT NULL,
        hash varchar(64) NOT NULL,
        page BLOB NOT NULL,
        createdDate DATETIME NOT NULL,
        PRIMARY KEY (`id`)
    )";
    $result = $mysqli->query($query);
    $result = $mysqli->query("ALTER TABLE $pageTable ADD INDEX hash(hash)");
}

$page = 0;
do {
    $queryUrl = "SELECT * FROM $urlTable LIMIT $page, ". PAGINATION;
    $resultUrl = $mysqli->query($queryUrl);
    while($rowUrl = $resultUrl->fetch_assoc()){
        //fetch the page
        $url = $rowUrl['url'];
        $hash = $rowUrl['hash'];
        $dup = $mysqli->query("SELECT * FROM $pageTable WHERE hash='$hash'");
        if($dup->num_rows == 0){
            $html = file_get_contents($url);
            $blob = addslashes(gzencode($html));
            $query = "INSERT INTO $pageTable (url, hash, page, createdDate) VALUES ('$url', '$hash', '$blob', now())";
            $result = $mysqli->query($query);
            echo $url.PHP_EOL;
            $rest = rand(1,10);
            echo "Sleep $rest seconds ... ";
            sleep($rest);
        }
    }
    if($resultUrl->num_rows == PAGINATION){
        $page += PAGINATION;
    }
    $rest = rand(1,10);
    echo "Change to page $page. Sleep for $rest seconds ... ".PHP_EOL;
    sleep($rest);
} while(true);
