<?php
namespace App\Background\Helpers;


class SlackMessageHelper
{
    public static function getNewNotVerifiedItemsMessage(string $fileName)
    {
        return ':bangbang: There are new (not verified) items, file: ' . $fileName . ' :bangbang:';
    }

    public static function getNoCustomFileMessage(string $fileName)
    {
        return ':bangbang: There is no custom.csv file: ' . $fileName . ' :bangbang:';
    }

    public static function getNewNotVerifiedRetailersMessage($customFileName)
    {
        return ':bangbang: There are new (not verified) retailers, file: ' . $customFileName . ' :bangbang:';
    }

    public static function getNoCustomRetailersFileMessage($customFileName)
    {
        return ':bangbang: There is no custom.csv file: ' . $customFileName . ' :bangbang:';
    }
}
