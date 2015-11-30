<?php
namespace App;

include '_autoload.php';

use App\Classes\Cart;
use App\Classes\CSVReader;
use App\Classes\PackMan;

/*
 * We have to invoke the CSV Reader to read the items from the data source.
 * All data files ar located under {project}/datasets folder
 */
$items = CSVReader::read('datasets/items.csv');

/*
 * Initiate the cart
 */
$cart = new Cart();

/*
 * Handle POST - Place Order
 * Adds an item on the cart
 */
if (isset($_POST['place_order'])) {

    //get the item details
    $name = $_POST['item_name'];
    $weight = $_POST['item_weight'];
    $price = $_POST['item_price'];

    //add the item to cart
    $cart->add($name, $weight, $price);
}

/*
 * Handle GET - Clear Cart
 * Removes all items from the cart
 */
if (isset($_GET['clearcart']) && $_GET['clearcart'] == 'true') {
    //clear item inside the cart and redirect to root
    $cart->clear();
    header('location:/');
}

?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <title>PackMan - An intelligent packaging manager</title>

    <!-- Bootstrap -->
    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://bootswatch.com/united/bootstrap.min.css">
    <!-- Google Font: Source Sans Pro -->
    <link href='https://fonts.googleapis.com/css?family=Source+Sans+Pro:400,200,300,600' rel='stylesheet' type='text/css'>
    <link href='assets/css/style.css' rel='stylesheet' type='text/css'>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>
<body>
<div class="container">
    <div class="header">
        <h1>
            <i class="logo fa fa-cubes"></i> PackMan
            <!-- Display the summary of the cart -->
            <p class="cart pull-right">
                <span><i class="fa fa-shopping-cart"></i> $<?php echo number_format($cart->getPrice(), 2) ?></span>
                <span><i class="fa fa-balance-scale"></i> <?php echo $cart->getWeight() ?>g</span>
            </p>
        </h1>
    </div>
    <div class="row">
        <div class="col-md-4">
            <div class="panel panel-default">
                <div class="panel-heading">Available Items</div>
                <div class="panel-body items-panel">
                    <!-- Display a vertical list of all the items available in-store -->
                    <?php
                    foreach($items as $item) {
                    ?>
                    <div class="item">
                        <form method="POST" name="order-form">
                            <input name="item_name" type="hidden" value="<?php echo $item['name'] ?>">
                            <input name="item_weight" type="hidden" value="<?php echo $item['weight'] ?>">
                            <input name="item_price" type="hidden" value="<?php echo $item['price'] ?>">
                            <p><?php echo $item['name'] ?><span class="pull-right">$<?php echo number_format($item['price'], 2) ?></span></p>
                            <div class="clearfix"></div>
                            <label><i class="fa fa-balance-scale"></i> <?php echo $item['weight'] ?>g</label>
                            <button class="btn btn-success btn-xs pull-right" name="place_order" type="submit"><i class="fa fa-cart-plus"></i> Place Order</button>
                        </form>
                    </div>
                    <?php
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php
        //If there was an error adding the item to the cart, display it
        if (isset($_SESSION['error'])) {
        ?>
        <div class="col-md-8">
            <div class="alert alert-warning alert-dismissible fade in" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
                <strong>Sorry!</strong> <?php echo $_SESSION['error'] ?>
            </div>
        </div>
        <?php
            //Once displayed, clear the message
            unset($_SESSION['error']);
        }
        ?>
        <div class="col-md-8">
                <div class="panel panel-default">
                    <div class="panel-heading">Packaging <a href="/?clearcart=true" class="btn btn-danger btn-xs pull-right">Clear Cart</a></div>
                    <div class="panel-body packages-panel">
                        <div class="packages">
                            <!-- Initiate the PackMan and display package options -->
                            <?php
                            $packman = new PackMan($cart);
                            $index = 1;
                            $packages = $packman->getPackages();
                            if (count($packages) > 0) {
                                foreach ($packages as $package) {
                            ?>
                                <div class="col-md-4">
                                    <div class="package">
                                        <h2>Package <?php echo $index ?></h2>
                                        <div class="items">
                                            <?php
                                            foreach ($package->items as $item) {
                                                ?>
                                                <span class="item" data-toggle="tooltip" data-placement="top"
                                                      title="Weight: <?php echo $item['weight'] ?>g, Price: $<?php echo number_format($item['price'], 2) ?>"><?php echo $item['name'] ?></span>
                                            <?php
                                            }
                                            ?>
                                        </div>
                                        <label class="pull-left"><i class="fa fa-balance-scale"></i> <?php echo $package->weight ?>g</label>
                                        <label class="pull-left"><i class="fa fa-cube"></i> $<?php echo number_format($package->price, 2) ?></label>
                                        <label class="pull-right"> $<?php echo number_format($package->shipping_cost, 2) ?></label>
                                        <?php if ($packman->debug) echo "Capacity: ".$package->weight_capacity.'g<br/>' ?>
                                        <?php if ($packman->debug) echo "$/g: ".round($package->cost_per_gram, 2).'g<br/>' ?>
                                    </div>
                                </div>
                            <?php
                                    $index++;
                                }
                            } else {
                            ?>
                            <h4 class="text-center">Please start ordering to see your packaging combinations!</h4>
                            <?php
                            }
                            ?>
                        </div>
                    </div>
                </div>
        </div>
    </div>
</div>

<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
<!-- Include all compiled plugins (below), or include individual files as needed -->
<!-- Latest compiled and minified JavaScript -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
<script src="assets/js/script.js"></script>
</body>
</html>