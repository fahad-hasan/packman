<?php
/**
 * Created by PhpStorm.
 * User: floyd
 * Date: 28/11/15
 * Time: 10:36 AM
 *
 * Represents a shopping cart
 *
 * Public: add($name, $weight, $price), clear(), getItems(), getPrice(), getWeight(), sort($function)
 * Private: $items, save()
 *
 */

namespace App\Classes;

class Cart {

    private $items;
    public $weight;
    public $price;

    /*
     * Constructs the cart, with items saved in the session, or an empty cart
     * Returns: N/A
     */
    public function __construct() {
        if (isset($_SESSION['packman_cart_items'])) {
            $this->items = $_SESSION['packman_cart_items'];
            $this->weight = $_SESSION['packman_cart_weight'];
            $this->price = $_SESSION['packman_cart_price'];
        } else {
            $this->items = array();
            $this->weight = 0;
            $this->price = 0;
        }
    }

    /*
     * Saves the cart to the session
     * Returns: N/A
     */
    private function save() {
        $_SESSION['packman_cart_items'] = $this->items;
        $_SESSION['packman_cart_weight'] = $this->weight;
        $_SESSION['packman_cart_price'] = $this->price;
    }

    /*
     * Add an item to the cart and save it. Also check if the item is priced and weighs within the limit
     * Returns: N/A
     */
    public function add($name, $weight, $price) {
        if ($weight <= 5000 && $price <= 250) {
            $item = [];
            $item['name'] = $name;
            $item['weight'] = $weight;
            $item['price'] = $price;
            $this->weight += $weight;
            $this->price += $price;
            $this->items[] = $item;
            $this->save();
        } else {
            $_SESSION['error'] = "We can not process items costing more than $250.00 or weighing more than 5kgs at the moment!";
        }
    }

    /*
     * Clear all the items in the cart
     * Returns: N/A
     */
    public function clear() {
        $this->items = array();
        $this->weight = 0;
        $this->price = 0;
        $this->save();
    }

    /*
     * Get all items stored in the cart
     * Returns: array()
     */
    public function getItems() {
        return $this->items;
    }

    /*
     * Sort the cart based on the closure
     * Returns: N/A
     */
    public function sort($function) {
        usort($this->items, $function);
    }

}