<?php
/**
 * 筑码引擎 www.402.cn
 */

session_start();
$_SESSION = [];
session_destroy();
header('Location: index.php');
