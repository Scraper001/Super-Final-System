<?php


date_default_timezone_set("Asia/Manila");
$current_date = date("Y-m-d");
echo $current_date;

$program_date = "2026-06-06";

if ($current_date > $program_date) {
    echo "You cannot enroll. The enrollment period has ended.";
} else {
    echo "You may still enroll!";
}

?>