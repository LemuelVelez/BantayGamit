<?php
namespace App\Application\Services;
use App\Models\AuditLogModel;
class AuditService {
    public function log(?int $actorId,string $action,?string $entityType,?int $entityId,string $message,array $metadata=[]): void {
        (new AuditLogModel())->insert(['actor_user_id'=>$actorId,'action'=>$action,'entity_type'=>$entityType,'entity_id'=>$entityId,'message'=>$message,'metadata'=>$metadata?json_encode($metadata,JSON_UNESCAPED_SLASHES):null,'created_at'=>date('Y-m-d H:i:s')]);
    }
}
