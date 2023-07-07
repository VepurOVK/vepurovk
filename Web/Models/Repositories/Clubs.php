<?php declare(strict_types=1);
namespace openvk\Web\Models\Repositories;
use openvk\Web\Models\Entities\Club;
use openvk\Web\Models\Repositories\Aliases;
use Nette\Database\Table\ActiveRow;
use Chandler\Database\DatabaseConnection;

class Clubs
{
    private $context;
    private $clubs;
    
    function __construct()
    {
        $this->context = DatabaseConnection::i()->getContext();
        $this->clubs   = $this->context->table("groups");
    }
    
    private function toClub(?ActiveRow $ar): ?Club
    {
        return is_null($ar) ? NULL : new Club($ar);
    }
    
    function getByShortURL(string $url): ?Club
    {
        $shortcode = $this->toClub($this->clubs->where("shortcode", $url)->fetch());

        if ($shortcode)
            return $shortcode;

        $alias = (new Aliases)->getByShortcode($url);

        if (!$alias) return NULL;
        if ($alias->getType() !== "club") return NULL;

        return $alias->getClub();
    }
    
    function get(int $id): ?Club
    {
        return $this->toClub($this->clubs->get($id));
    }
    
    function find(string $query, array $pars = [], string $sort = "id DESC"): \Traversable
    {
        $query  = "%$query%";
        $result = $this->clubs->where("name LIKE ? OR about LIKE ?", $query, $query)->order("$sort");

        $notNullParams = [];
        $nnparamsCount = 0;

        foreach($pars as $paramName => $paramValue)
            if($paramName != "before" && $paramName != "after" && $paramName != "gender" && $paramName != "maritalstatus" && $paramName != "politViews" && $paramName != "doNotSearchMe")
                $paramValue != NULL ? $notNullParams += ["$paramName" => "%$paramValue%"] : NULL;
            else
                $paramValue != NULL ? $notNullParams += ["$paramName" => "$paramValue"]   : NULL;

        $nnparamsCount = sizeof($notNullParams);

        if($nnparamsCount > 0) {
            foreach($notNullParams as $paramName => $paramValue) {
                switch($paramName) {
                    case "doNotShowDeleted":
                        $result->where("deleted", 0);
                        break;
                }
            }
        }
        
        return new Util\EntityStream("Club", $result->order($sort));
    }

    function getCount(): int
    {
        return sizeof(clone $this->clubs);
    }

    function getPopularClubs(): \Traversable
    {
        $query   = "SELECT ROW_NUMBER() OVER (ORDER BY `subscriptions` DESC) as `place`, `target` as `id`, COUNT(`follower`) as `subscriptions` FROM `subscriptions` WHERE `model` = \"openvk\\\Web\\\Models\\\Entities\\\Club\" GROUP BY `target` ORDER BY `subscriptions` DESC, `id` LIMIT 20;";
        $entries = DatabaseConnection::i()->getConnection()->query($query);

        foreach($entries as $entry)
            yield (object) [
                "place"         => $entry["place"],
                "club"          => $this->get($entry["id"]),
                "subscriptions" => $entry["subscriptions"],
            ];
    }

    function getWriteableClubs(int $id): \Traversable
    {
        $result    = $this->clubs->where("owner", $id)->where("deleted", 0);
        $coadmins  = $this->coadmins->where("user", $id);
        
        foreach($result as $entry) {
            yield new Club($entry);
        }

        foreach($coadmins as $coadmin) {
            $cl = new Manager($coadmin);
            yield $cl->getClub();
        }
    }

    function getWriteableClubsCount(int $id): int
    {
        return sizeof($this->clubs->where("owner", $id)->where("deleted", 0)) + sizeof($this->coadmins->where("user", $id));
    }
    
    use \Nette\SmartObject;
}