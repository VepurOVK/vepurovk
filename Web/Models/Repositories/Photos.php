<?php declare(strict_types=1);
namespace openvk\Web\Models\Repositories;
use openvk\Web\Models\Entities\{Photo, User};
use Chandler\Database\DatabaseConnection;

class Photos
{
    private $context;
    private $photos;
    
    function __construct()
    {
        $this->context = DatabaseConnection::i()->getContext();
        $this->photos  = $this->context->table("photos");
    }
    
    function get(int $id): ?Photo
    {
        $photo = $this->photos->get($id);
        if(!$photo) return NULL;
        
        return new Photo($photo);
    }
    
    function getByOwnerAndVID(int $owner, int $vId): ?Photo
    {
        $photo = $this->photos->where([
            "owner"      => $owner,
            "virtual_id" => $vId,
            "system"     => 0,
            "private"    => 0,
        ])->fetch();
        if(!$photo) return NULL;
        
        return new Photo($photo);
    }

    function getEveryUserPhoto(User $user, int $offset = 0, int $limit = 10): \Traversable
    {
        $perPage = $perPage ?? OPENVK_DEFAULT_PER_PAGE;
        $photos = $this->photos->where([
            "owner"    => $user->getId(),
            "deleted"  => 0,
            "system"   => 0,
            "private"  => 0,
        ])->order("id DESC");

        foreach($photos->limit($limit, $offset) as $photo) {
            yield new Photo($photo);
        }
    }

    function getUserPhotosCount(User $user) 
    {
        $photos = $this->photos->where([
            "owner"    => $user->getId(),
            "deleted"  => 0,
            "system"   => 0,
            "private"  => 0,
        ]);

        return sizeof($photos);
    }
}
