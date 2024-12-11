<?php
/**
 * Created by PhpStorm.
 * User: spbaniya
 * Date: 8/2/17
 * Time: 2:35 PM
 */

class DeleteTestClass extends Migration implements MigrationInterface{

    public function __construct()
    {
        $this->baseTable = "TestParseUserClass";
    }

    public function up()
    {


    }

    public function down()
    {

    }

    public function drop()
    {
        $this->drop = true;
    }

    public function create()
    {
        $this->create = false;
    }
}
