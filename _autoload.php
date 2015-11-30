<?php
/**
 * Created by PhpStorm.
 * User: floyd
 * Date: 28/11/15
 * Time: 7:58 AM
 *
 * Loads all required dependencies, placed in a single file for ease of use
 *
 */

//First things first, lets start the session
session_start();

//Include all the dependencies
include 'classes/CSVReader.class.php';
include 'classes/Cart.class.php';
include 'classes/Package.class.php';
include 'classes/PackMan.class.php';