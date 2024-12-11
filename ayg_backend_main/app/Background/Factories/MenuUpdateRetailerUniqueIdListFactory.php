<?php
namespace App\Background\Factories;

use App\Background\Entities\MenuUpdateRetailerUniqueId;
use App\Background\Entities\MenuUpdateRetailerUniqueIdList;
use App\Background\Entities\RetailerPartner;
use App\Background\Repositories\RetailerPartnerRepositoryInterface;

class MenuUpdateRetailerUniqueIdListFactory
{
    public static function create(RetailerPartnerRepositoryInterface $retailerPartnerRepository)
    {
        $retailersToProcess['BOS'] = [
            ["directory" => 'BGood-A', "uniqueId" => 'd8f022165609f89cf21fef4e97751297'],
            ["directory" => 'BGood-B', "uniqueId" => '50deccc67bee5e245ddf920c7129f47f'],
            ["directory" => 'Wolfgang', "uniqueId" => '3c6ef73e0c8ad898739bdc53a62e8f6d'],
            ["directory" => 'Bruins', "uniqueId" => '87daed79bdf2ff29e85dbf5cb04bd666'],
            ["directory" => 'BurgerKing', "uniqueId" => '71d582ab56171e76dd7b1b274866ea83'],
            ["directory" => 'Lucca', "uniqueId" => '2644ec1d68cff291ac34b0377dc7b0cb'],
            ["directory" => 'NYAJ', "uniqueId" => '3f86383fca8a51a328f8927612b744b9'],
            ["directory" => 'Mija', "uniqueId" => '7a849231a7105249568df62f11da6304'],
            ["directory" => 'LTK', "uniqueId" => 'ca4d6e524a2d19fdcef6eed33d0ddd33'],
            ["directory" => 'Currito', "uniqueId" => 'eff3cbad1271f671396518ab0436c64b'],
            ["directory" => 'Monicas', "uniqueId" => 'd7866bd4e4f13862e4921c59ce3246df'],
        ];
        $retailersToProcess['DEN'] = [
            ["directory" => 'McDonalds-3', "uniqueId" => 'd85ad4c530731e05e4c5b924b376f96f'],
            ["directory" => 'DCM', "uniqueId" => '87cfcb12e0adc902ef8bcd73c98bf286'],
            ["directory" => 'Einstein', "uniqueId" => 'f90cb4bf1cc2b1d6bb41369def6fc3af'],
            ["directory" => 'Garbanzos', "uniqueId" => '6b65590c2a68b03c699abbf4b8d8e45d'],
            ["directory" => 'RootDown', "uniqueId" => '55332fb06a8e00a439e8785aac528da7'],
            ["directory" => 'Timberline', "uniqueId" => 'ed297b1ddf43a9b1986688fb3e4c450f'],
            ["directory" => 'VinoVolo', "uniqueId" => '20cf766845ccdca412a5e603991a7d3c'],
            ["directory" => 'PizzaHut', "uniqueId" => '84a200cf32ba0978e10d9a043acf6a20'],
            ["directory" => 'Sees', "uniqueId" => '968689038e1599d24697342355de250d'],
            ["directory" => 'FlightStop', "uniqueId" => 'fa3ea1e4cc85a1f379259917330f9f80'],
            ["directory" => 'Peak', "uniqueId" => 'd9a6537ef13a0b0a1e15c7113a6401a3'],
            ["directory" => 'KFC', "uniqueId" => '1daf3d93edf8626aa22282b03340760f'],
            ["directory" => 'Etais', "uniqueId" => '6ffffe5bccbeff24e63edf594c3d9d58'],
            ["directory" => 'Quiznos', "uniqueId" => 'bc6c91782a4e1b3e6fbca1c950ea3250'],
            ["directory" => 'QueBueno', "uniqueId" => 'bd53a0f97f6d36772e90ed2c62efd87b'],
            ["directory" => 'Holiday', "uniqueId" => '7f1c73bc8a6516e8eed46d9ad71e4cb2'],
            ["directory" => 'VinoVolo-A49', "uniqueId" => 'e4a45cff037f0064a091ebd702f76dd8'],
        ];
        $retailersToProcess['EWR'] = [
            ["directory" => 'Dunkin', "uniqueId" => '2ddc0a9bd6fbe2aff5268458fc00dafa'],
            ["directory" => 'JerseyMikes', "uniqueId" => '73f14a7ee6f276bfc4e6760d25ceb260'],
            ["directory" => 'Salsaritas', "uniqueId" => '28d7f8577d98d530b85fcb03a8739fec'],
            ["directory" => 'TonyBennys', "uniqueId" => '429e9491cd4cfdc5da5e21ea297b39df'],
            ["directory" => 'Villa', "uniqueId" => '0c98327835a0fb04e7c6b29e4c214596'],
            ["directory" => 'Malones', "uniqueId" => '3d198093971365b1906044f580dc5344'],
            ["directory" => 'Firehouse', "uniqueId" => 'bd3800cb6dde636b37407f79a08671b5'],
            ["directory" => 'Panda', "uniqueId" => '07cd64beeeb70d81e1ddfd2dea8bd439'],
            ["directory" => 'Smash', "uniqueId" => '0966dbc3b3cf48a254c680009a8d9c75'],
            ["directory" => 'Qdoba', "uniqueId" => 'a72ac3af3cae66ba221a6ecfefa88c1b'],
            ["directory" => 'JerseyChix', "uniqueId" => 'dd18dce8de8e69d5c5795f9938941b6a'],
        ];
        $retailersToProcess['JFK'] = [
            ["directory" => 'McDonalds-4', "uniqueId" => '8ab167a16304d35559cbc5d47140ed90'],
            ["directory" => 'Abitinos', "uniqueId" => '0dbc1894cb08f8450d935c24761f62a2'],
            ["directory" => 'Dunkin', "uniqueId" => 'c04f81b4997a94777111bab9f4f7d792'],
            ["directory" => 'MezzeCafe', "uniqueId" => 'f16d878a9f582aafa0108b06fc8a7d46'],
            ["directory" => 'MiCasa', "uniqueId" => '097d10d6d62c258eda9b0d947fdaa03e'],
            ["directory" => 'PizzaVino', "uniqueId" => 'e5a405a1c4d30efd17cd390a4e271252'],
            ["directory" => 'Camden', "uniqueId" => '43b10840be79c5f2ca13e5ee0a9430ec'],
            ["directory" => 'BWW-limitedversion', "uniqueId" => 'c69f9aa3a9a01b8f37b4f55ba50ee74e'],
            ["directory" => 'TheBar', "uniqueId" => '7168b17698ff4507ab6c1b0687ad5b5b'],
            ["directory" => 'Kosher', "uniqueId" => 'c019070f3989768615685d1c9a58c008'],
            ["directory" => 'ShakeShack', "uniqueId" => '9270a803f2e6509d4c3a158c4b89ff89'],
            ["directory" => 'Jamba', "uniqueId" => '2bb1cafa8f63bb983e17c55673c402bd'],
            ["directory" => 'Bento', "uniqueId" => 'a8a9cc915065d3192b90344ca7c78e34'],
            ["directory" => 'ShakeShack-B37', "uniqueId" => '4419bf4e59fbe460fe0b1d88e76ef751'],

        ];
        $retailersToProcess['LGA'] = [
            ["directory" => 'AuntieAnnes', "uniqueId" => 'c634cf4ef0c0111905728fbe2ae4d00c'],
            ["directory" => 'DosToros', "uniqueId" => '7acff17a15dd0ede71bf61a72dbd9831'],
            ["directory" => 'Juniors', "uniqueId" => '30f1c52dd31a2ae4441692dd0d92776a'],
            ["directory" => 'ShakeShack', "uniqueId" => 'c64b255a54d3a4edb05049a8008be5d0'],
            ["directory" => 'TonyBennys', "uniqueId" => 'ec8e0ece7bfc59b4d2d41dcec0ddf615'],
            ["directory" => 'Wendys', "uniqueId" => '18bed2b17aabda666b1272ffd8d93a99'],
            ["directory" => 'Dunkin', "uniqueId" => 'ce86eaa1f64628959787ca9aad014e85'],
        ];
        $retailersToProcess['MSP'] = [
            ["directory" => 'PandaExpress', "uniqueId" => '9d1d7e5fd5c33e342497fdacc483e190'],
            ["directory" => 'Brueggers', "uniqueId" => '2fc7394e98e368d301dc2c9f9500d204'],
            ["directory" => 'HolyLand', "uniqueId" => '1b7bcd1cffaa826ed474abdb02da4b4c'],
            ["directory" => 'Ikes', "uniqueId" => 'e374bbafd39c9fea0b1ebc4d61900a5f'],
            ["directory" => 'McDonalds', "uniqueId" => '303a6e33a8803dfcf120dd0062481d65'],
            ["directory" => 'NorthLoopMarket', "uniqueId" => '85b3c192ce49cdd8879a97c32fa53047'],
            ["directory" => 'Qdoba', "uniqueId" => '2bce99b0a829b1098372bf78b8dfde4b'],
            ["directory" => 'RedCow', "uniqueId" => '8a8a8c7143999ab09b0d036b4b51b303'],
            ["directory" => 'TwinsGrill', "uniqueId" => '616ee3594f14980c5edabb248c1d2a89'],
            ["directory" => 'SmackShack', "uniqueId" => 'f6d90fe426ad316ecea588ff1c6f32a8'],
        ];
        $retailersToProcess['PDX'] = [
            ["directory" => 'Bambuza', "uniqueId" => '882f333b923c00aa43a444382152576c'],
            ["directory" => 'BangkokExpress', "uniqueId" => '974587cb0dfe54f6e0ed0eb3c1643cb3'],
            ["directory" => 'Beaches', "uniqueId" => 'abc8acf22d5a5bfa1e3aa02f1802cbf9'],
            ["directory" => 'CafeYumm', "uniqueId" => 'dc12811d84289faebcca7f6df41fe825'],
            ["directory" => 'HUB', "uniqueId" => 'dad9c5723e6f19e9aa622d3952204633'],
            ["directory" => 'Henrys', "uniqueId" => 'e81b92a112770328677f3b6e5aa7aece'],
            ["directory" => 'Hissho', "uniqueId" => '91b77c966679c05d53c44a11d16a69be'],
            ["directory" => 'Panda', "uniqueId" => 'ad10bb8b431e78d725795e642268e580'],
            ["directory" => 'PR', "uniqueId" => '61521dd9c3acd23217329e7026b96cb8'],
            ["directory" => 'TillamookMarket', "uniqueId" => '08510b8aee7bfbca41d2e581930b3cef'],
            ["directory" => 'Sumo', "uniqueId" => '5cc380fc32d15ea961ffd5e065c3e8d7'],
            ["directory" => 'TLE', "uniqueId" => '3fd4d8d988435febc31b27843255aafe'],
            ["directory" => 'Evergreens', "uniqueId" => '7b4636d0200cf07ee902119ba7fc8f5e'],
            ["directory" => 'Burgerville', "uniqueId" => '8a88a08f2949c17d620cec21daa764d3'],
            ["directory" => 'Deschutes', "uniqueId" => '93d176ddb226eaf5a3d19a80f44956fe'],
        ];
        $retailersToProcess['SAN'] = [
            ["directory" => 'Artisan', "uniqueId" => '891b75f03f979d4c09948afb7e58e2fc'],
            ["directory" => 'Camden', "uniqueId" => '5c574e4d51091a8ccc3eb25070c69642'],
            ["directory" => 'Einstein', "uniqueId" => '1e7448d98dd57b7daa5f5e084c2435cb'],
            ["directory" => 'JackBox', "uniqueId" => '9c4382855f95aeaf09f59aabe9691a9c'],
            ["directory" => 'PeetsT2', "uniqueId" => 'b0ac65aee4bf8bc62efd12c2e45ecea8'],
            ["directory" => 'Qdoba', "uniqueId" => 'c177da5151406844f18e62142f1557f3'],
            ["directory" => 'CNBC', "uniqueId" => '6f42aba90bf37fe985c5f2cea0daf4cb'],
            ["directory" => 'Panda', "uniqueId" => '802a9a140a309862c1738caa5216039e'],
            ["directory" => 'Phils', "uniqueId" => 'c236fdb78dc4f408b73b848c9fff0a95'],
            ["directory" => 'ArtisanMarket', "uniqueId" => '93de3a19f6f7048652ae2ab569d788aa'],
            ["directory" => 'Pannikin', "uniqueId" => '58731188a5db43b783784681a2f6cc72'],
        ];


        // grab integration:
        $retailerPartnerList = $retailerPartnerRepository->getListByPartnerName('grab');
        /** @var RetailerPartner $retailerPartner */
        foreach ($retailerPartnerList as $retailerPartner) {
            $uniqueId = $retailerPartner->getRetailerUniqueId();
            $airportIataCode = $retailerPartner->getAirportIataCode();
            $directory = preg_replace('/^'.$retailerPartner->getAirportIataCode().'\-/', '', $retailerPartner->getItemsDirectoryName());
            $retailersToProcess[$airportIataCode][] = ["directory" => $directory, "uniqueId" => $uniqueId];
        }


        $retailerUniqueIdLoadDataList = new MenuUpdateRetailerUniqueIdList([]);

        foreach ($retailersToProcess as $airportIataCode => $retailers) {
            foreach ($retailers as $retailer) {
                $retailerUniqueIdLoadDataList->addItem(
                    new MenuUpdateRetailerUniqueId(
                        $airportIataCode,
                        $retailer['uniqueId'],
                        $retailer['directory']
                    ));
            }
        }

        return $retailerUniqueIdLoadDataList;
    }
}
