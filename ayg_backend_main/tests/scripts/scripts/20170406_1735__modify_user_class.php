<?php
/**
 * Created by PhpStorm.
 * User: spbaniya
 * Date: 8/2/17
 * Time: 2:35 PM
 */

class ModifyUserClass extends Migration implements MigrationInterface{

    public function __construct()
    {
        $this->baseTable = "TestParseUserClass";
    }

    public function up()
    {
        $this->addField("Address", "String");
        $this->addField("Phone", "String");
    }

    public function down()
    {
        $this->removeField("DateOfBirth", "Date");
    }

    public function drop()
    {
        $this->drop = false;
    }

    public function create()
    {
        $this->create = false;
    }
}