<?php 

function getAllProducts() {
    $sql = "SELECT * FROM products";
    $result = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
    return $result;
}
?>