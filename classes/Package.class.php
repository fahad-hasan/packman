<?php
/**
 * Created by PhpStorm.
 * User: floyd
 * Date: 28/11/15
 * Time: 10:36 AM
 *
 * Represent a single package
 *
 * Public: $items, $weight, $weight_capacity, $price, $price_capacity, $shipping_cost, add(array $item), canHold()
 * Private: calculateShipping()
 *
 */

namespace App\Classes;

class Package {

    public $items = array();            //holds the items
    public $weight = 0;                 //total weight of all items in the package
    public $weight_capacity = 5000;     //capacity remaining until 5kg
    public $weight_capacity_sh = 200;   //capacity remaining without increasing shipping
    public $price = 0;                  //total price of all items in the package
    public $price_capacity = 250;       //price remaining until $250
    public $shipping_cost = 0;          //current calculated shipping cost based on the current weight
    public $cost_per_gram = 20;         //cost per gram

    /*
     * Adds an item in the package, recalculates the package attributes
     * Returns: N/A
     */
    public function add(array $item) {
        $this->items[] = $item;
        $this->weight += $item['weight'];
        $this->weight_capacity -= $item['weight'];
        $this->weight_capacity_sh = $this->calculateWeightCapacityWithoutIncreasingShipping();
        $this->price += $item['price'];
        $this->price_capacity -= $item['price'];
        $this->shipping_cost = $this->calculateShipping();
        $this->cost_per_gram = $this->shipping_cost/$this->weight;
    }

    /*
     * Check if the package has enough capacity, price and weight wise, to hold the $item
     * Returns: boolean
     */
    public function canHold($item) {
        return $this->price_capacity >= $item['price'] && $this->weight_capacity >= $item['weight'];
    }

    /*
     * Calculates the shipping based on the weight of the package
     * Returns: integer
     */
    private function calculateShipping() {
        if ($this->weight >= 0 && $this->weight <= 200) {
            return 5;
        } else if ($this->weight >= 201 && $this->weight <= 500) {
            return 10;
        } else if ($this->weight >= 501 && $this->weight <= 1000) {
            return 15;
        } else if ($this->weight >= 1001 && $this->weight <= 5000) {
            return 20;
        }
    }

    private function calculateWeightCapacityWithoutIncreasingShipping() {
        if ($this->weight >= 0 && $this->weight <= 200) {
            return (200 - $this->weight);
        } else if ($this->weight >= 201 && $this->weight <= 500) {
            return (500 - $this->weight);
        } else if ($this->weight >= 501 && $this->weight <= 1000) {
            return (1000 - $this->weight);
        } else if ($this->weight >= 1001 && $this->weight <= 5000) {
            return (5000 - $this->weight);
        }
    }
}