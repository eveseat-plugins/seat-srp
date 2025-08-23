<?php

namespace CryptaTech\Seat\SeatSrp\Helpers;

use CryptaTech\Seat\SeatSrp\Enum\SRPCategoryEnum;
use CryptaTech\Seat\SeatSrp\Items\PriceableSRPItem;
use CryptaTech\Seat\SeatSrp\Models\AdvRule;
use CryptaTech\Seat\SeatSrp\Models\Eve\Insurance;
use CryptaTech\Seat\SeatSrp\Models\Sde\InvFlag;
use Illuminate\Support\Collection;
use RecursiveTree\Seat\PricesCore\Exceptions\PriceProviderException;
use RecursiveTree\Seat\PricesCore\Facades\PriceProviderSystem;
use Seat\Eveapi\Models\Character\CharacterInfo;
use Seat\Eveapi\Models\Killmails\Killmail;

trait SrpManager
{

    public static $FIT_FLAGS = [
        11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 87,
        92, 93, 94, 95, 96, 97, 98, 99, 125, 126, 127, 128, 129, 130, 131, 132, 158, 159, 160, 161, 162, 163,
    ];
    public static $CARGO_FLAGS = [5, 90, 133, 134, 135, 136, 137, 138, 139, 140, 141, 142, 143, 148, 149, 151, 155, 176, 177, 179];

    private function srpPopulateSlots(Killmail $killMail): array
    {
        $priceList = [];
        $slots = [
            'killId' => 0,
            'price' => 0.0,
            'shipType' => null,
            'characterName' => null,
            'cargo' => [],
            'dronebay' => [],
        ];
        // dd($killMail->victim->items);
        foreach ($killMail->victim->items as $item) {
            $searchedItem = $item;
            $slotName = InvFlag::find($item->pivot->flag);
            if (! is_object($searchedItem)) {
            } else {
                $priceitem = array_key_exists($searchedItem->typeID, $priceList) ? $priceList[$searchedItem->typeID] : new PriceableSRPItem($searchedItem, $item->pivot->flag, 0);

                switch ($slotName->flagName) {
                    case 'Cargo':
                        $slots['cargo'][$searchedItem->typeID]['name'] = $searchedItem->typeName;
                        if (! isset($slots['cargo'][$searchedItem->typeID]['qty']))
                            $slots['cargo'][$searchedItem->typeID]['qty'] = 0;
                        if (! is_null($item->pivot->quantity_destroyed))
                            $slots['cargo'][$searchedItem->typeID]['qty'] += $item->pivot->quantity_destroyed;
                        if (! is_null($item->pivot->quantity_dropped))
                            $slots['cargo'][$searchedItem->typeID]['qty'] += $item->pivot->quantity_dropped;
                        break;
                    case 'DroneBay':
                        $slots['dronebay'][$searchedItem->typeID]['name'] = $searchedItem->typeName;
                        if (! isset($slots['dronebay'][$searchedItem->typeID]['qty']))
                            $slots['dronebay'][$searchedItem->typeID]['qty'] = 0;
                        if (! is_null($item->pivot->quantity_destroyed))
                            $slots['dronebay'][$searchedItem->typeID]['qty'] += $item->pivot->quantity_destroyed;
                        if (! is_null($item->pivot->quantity_dropped))
                            $slots['dronebay'][$searchedItem->typeID]['qty'] += $item->pivot->quantity_dropped;
                        break;
                    default:
                        if (! preg_match('/(Charge|Script|[SML])$/', $searchedItem->typeName)) {
                            $slots[$slotName->flagName]['id'] = $searchedItem->typeID;
                            $slots[$slotName->flagName]['name'] = $searchedItem->typeName;
                            if (! isset($slots[$slotName->flagName]['qty']))
                                $slots[$slotName->flagName]['qty'] = 0;
                            if (! is_null($item->pivot->quantity_destroyed))
                                $slots[$slotName->flagName]['qty'] += $item->pivot->quantity_destroyed;
                            if (! is_null($item->pivot->quantity_dropped))
                                $slots[$slotName->flagName]['qty'] += $item->pivot->quantity_dropped;
                        }
                        break;
                }
                // Yes all of this should be neater... Deal with it for now.
                if (! is_null($item->pivot->quantity_destroyed))
                    $priceitem->incrementAmount($item->pivot->quantity_destroyed);
                if (! is_null($item->pivot->quantity_dropped))
                    $priceitem->incrementAmount($item->pivot->quantity_dropped);
                $priceList[$searchedItem->typeID] = $priceitem;
                // array_push($priceList, $priceitem);
            }
        }

        $searchedItem = $killMail->victim->ship;
        $slots['typeId'] = $killMail->victim->ship->typeID;
        $slots['shipType'] = $searchedItem->typeName;
        array_push($priceList, new PriceableSRPItem($searchedItem, 0, 1));

        // dd($priceList, $slots);

        $priceList = collect($priceList);

        $prices = $this->srpGetPrice($killMail, $priceList);

        $pilot = CharacterInfo::find($killMail->victim->character_id);

        $slots['characterName'] = $killMail->victim->character_id;
        if (! is_null($pilot))
            $slots['characterName'] = $pilot->name;

        $slots['killId'] = $killMail->killmail_id;
        $slots['price'] = $prices;

        return $slots;
    }

    private function srpGetPrice(Killmail $killmail, Collection $priceList): array
    {
        // Switching logic between advanced and simple rules
        // Try advanced first, becasue if the setting hasnt been set it will be empty.
        if (setting('cryptatech_seat_srp_advanced_srp', true) == '1') {
            return $this->srpGetAdvancedPrice($killmail, $priceList);
        }

        return $this->srpGetSimplePrice($killmail, $priceList);
    }

    private function srpGetAdvancedPrice(Killmail $killmail, Collection $priceList): array
    {
        // Start by checking if there is a type rule that matches the ship
        $rule = AdvRule::where('type_id', $killmail->victim->ship_type_id)->first();
        if (is_null($rule)) {
            $rule = AdvRule::where('group_id', $killmail->victim->ship->groupID)->first();
            if (is_null($rule)) {
                return  $this->srpGetDefaultRulePrice($killmail, $priceList);
            }
        }

        return $this->srpGetRulePrice($rule, $killmail, $priceList);
    }

    private function srpGetRulePrice(AdvRule $rule, Killmail $killmail, Collection $priceList): array
    {

        $source = $rule->price_source;
        $base_value = $rule->base_value;
        $hull_percent = $rule->hull_percent / 100;
        $fit_percent = $rule->fit_percent / 100;
        $cargo_percent = $rule->cargo_percent / 100;
        $deduct_insurance = $rule->deduct_insurance;
        $price_cap = $rule->srp_price_cap;

        $deduct_insurance = $deduct_insurance == '1' ? true : false;

        foreach ($priceList as $item) {

            match ($item->getSRPCategory())
            {
                SRPCategoryEnum::SHIP => $item->setModifier($hull_percent),
                SRPCategoryEnum::CARGO => $item->setModifier($cargo_percent),
                SRPCategoryEnum::FITTING => $item->setModifier($fit_percent),
                SRPCategoryEnum::MISC => $item->setModifier(0),
            };
        }

        // Hydrate all the prices
        try {
            PriceProviderSystem::getPrices($rule->price_source, $priceList);
        } catch (PriceProviderException $e) {
            return [
                'price' => 0,
                'rule' => $rule->rule_type,
                'error' => $e->getMessage(),
                'source' => $source,
                'base_value' => $base_value,
                'hull_percent' => $hull_percent,
                'fit_percent' => $fit_percent,
                'cargo_percent' => $cargo_percent,
                'deduct_insurance' => $deduct_insurance,
            ];
            // return redirect()->back()->with("error", "Failed to get prices from price provider: $message");
        }

        $value = $priceList->sum(function (PriceableSRPItem $item) {
            // Log::warning([$item->getTypeID(), $item->type()->typeName, $item->getPrice(), $item->getAmount(), $item->getSRPPrice()]);
            return $item->getSRPPrice();
        });
        // dd($priceList, $value);

        $total = $value + $base_value;

        if ($deduct_insurance) {
            $ins = Insurance::where('type_id', $killmail->victim->ship_type_id)->where('Name', 'Platinum')->first();
            if (! is_null($ins)) {
                $total = $total + $ins->cost - $ins->payout;
            }
        }

        $total = round($total, 2);

        //apply price cap
        if ($price_cap !== null && $total > $price_cap) {
            $total = $price_cap;
        }

        return [
            'price' => $total,
            'error' => 'None',
            'rule' => $rule->rule_type,
            'source' => $source,
            'base_value' => $base_value,
            'hull_percent' => $hull_percent,
            'fit_percent' => $fit_percent,
            'cargo_percent' => $cargo_percent,
            'deduct_insurance' => $deduct_insurance,
        ];
    }

    private function srpGetDefaultRulePrice(Killmail $killmail, Collection $priceList): array
    {

        $source = setting('cryptatech_seat_srp_advrule_def_source', true) ? setting('cryptatech_seat_srp_advrule_def_source', true) : 0;
        $base_value = setting('cryptatech_seat_srp_advrule_def_base', true) ? setting('cryptatech_seat_srp_advrule_def_base', true) : 0;
        $hull_percent = setting('cryptatech_seat_srp_advrule_def_hull', true) ? setting('cryptatech_seat_srp_advrule_def_hull', true) : 0;
        $fit_percent = setting('cryptatech_seat_srp_advrule_def_fit', true) ? setting('cryptatech_seat_srp_advrule_def_fit', true) : 0;
        $cargo_percent = setting('cryptatech_seat_srp_advrule_def_cargo', true) ? setting('cryptatech_seat_srp_advrule_def_cargo', true) : 0;
        $deduct_insurance = setting('cryptatech_seat_srp_advrule_def_ins', true) ? setting('cryptatech_seat_srp_advrule_def_ins', true) : 0;
        $price_cap = setting('cryptatech_seat_srp_advrule_def_price_cap', true) ? intval(setting('cryptatech_seat_srp_advrule_def_price_cap', true)) : null;

        $rule = new AdvRule([
            'rule_type' => 'default',
            'price_source' => $source,
            'base_value' => $base_value,
            'hull_percent' => $hull_percent,
            'cargo_percent' => $cargo_percent,
            'fit_percent' => $fit_percent,
            'srp_price_cap' => $price_cap,
            'deduct_insurance' => $deduct_insurance,
        ]);

        return $this->srpGetRulePrice($rule, $killmail, $priceList);

    }

    private function srpGetSimplePrice(Killmail $killmail, Collection $priceList): array
    {
        $rule = new AdvRule([
            'rule_type' => 'simple',
            'price_source' => setting('cryptatech_seat_srp_simple_source', true),
            'base_value' => 0,
            'hull_percent' => 100,
            'cargo_percent' => 100,
            'fit_percent' => 100,
            'deduct_insurance' => 0,
        ]);

        return $this->srpGetRulePrice($rule, $killmail, $priceList);
    }
}
