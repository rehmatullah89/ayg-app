<?php
/**
 * Created by PhpStorm.
 * User: spbaniya
 * Date: 8/2/17
 * Time: 2:35 PM
 */

class AddUser extends Migration implements MigrationInterface{

    public function __construct()
    {
        $this->baseTable = "TestParseUserClass";
    }

    public function up()
    {
        $this->addField("First_Name", "String");
        $this->addField("Last_Name", "String");
        $this->addField("DateOfBirth", "Date");
    }

    public function down()
    {
    }

    public function drop()
    {
        $this->drop = false;
    }

    public function create()
    {
        $this->create = true;
    }
}