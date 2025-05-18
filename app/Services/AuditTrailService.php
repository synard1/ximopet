<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use App\Models\AuditTrail;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AuditTrailService
{
    /**
     * Record a deletion operation and its related entities in the audit trail
     *
     * @param Model|string $model The model or model class name being deleted
     * @param string|int $modelId The ID of the model being deleted
     * @param array $relatedRecords Optional array of related records that were also deleted
     * @param array $additionalInfo Optional additional context information
     * @param string $reason Optional reason for deletion
     * @return \App\Models\AuditTrail
     */
    public static function logDeletion($model, $modelId, array $relatedRecords = [], array $additionalInfo = [], string $reason = null)
    {
        try {
            DB::beginTransaction();
            
            // Get model information
            $modelClass = is_object($model) ? get_class($model) : $model;
            $modelName = class_basename($modelClass);
            
            // Get user information
            $userId = Auth::id();
            $userInfo = self::getUserInfo();
            
            // Format related records for storage
            $formattedRelatedRecords = self::formatRelatedRecords($relatedRecords);
            
            // Create record in the audit trail table
            $auditTrail = AuditTrail::create([
                'user_id' => $userId,
                'action' => 'delete',
                'model_type' => $modelClass,
                'model_name' => $modelName,
                'model_id' => $modelId,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'reason' => $reason,
                'related_records' => $formattedRelatedRecords,
                'additional_info' => $additionalInfo,
                'user_info' => $userInfo,
                'timestamp' => now(),
            ]);
            
            DB::commit();
            
            // Log the deletion for server-side record
            Log::info("Deletion audit trail recorded", [
                'audit_trail_id' => $auditTrail->id,
                'model' => $modelName,
                'model_id' => $modelId,
                'user_id' => $userId,
                'related_records_count' => count($relatedRecords),
            ]);
            
            return $auditTrail;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Log the error but don't throw it to prevent disrupting the application flow
            Log::error("Failed to record deletion in audit trail", [
                'error' => $e->getMessage(),
                'model' => is_object($model) ? get_class($model) : $model,
                'model_id' => $modelId,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            // Return null to indicate failure
            return null;
        }
    }
    
    /**
     * Record a batch deletion operation
     *
     * @param string $modelClass The class of models being deleted
     * @param array $modelIds Array of IDs being deleted
     * @param string $reason Optional reason for the batch deletion
     * @param array $additionalInfo Optional additional context
     * @return \App\Models\AuditTrail
     */
    public static function logBatchDeletion($modelClass, array $modelIds, string $reason = null, array $additionalInfo = [])
    {
        try {
            $modelName = class_basename($modelClass);
            $userId = Auth::id();
            $userInfo = self::getUserInfo();
            
            $auditTrail = AuditTrail::create([
                'user_id' => $userId,
                'action' => 'batch_delete',
                'model_type' => $modelClass,
                'model_name' => $modelName,
                'model_id' => null, // No single ID for batch operations
                'model_ids' => $modelIds, // Store all affected IDs
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'reason' => $reason,
                'additional_info' => array_merge($additionalInfo, [
                    'count' => count($modelIds),
                    'ids' => $modelIds
                ]),
                'user_info' => $userInfo,
                'timestamp' => now(),
            ]);
            
            Log::info("Batch deletion audit trail recorded", [
                'audit_trail_id' => $auditTrail->id,
                'model' => $modelName,
                'count' => count($modelIds),
                'user_id' => $userId,
            ]);
            
            return $auditTrail;
            
        } catch (\Exception $e) {
            Log::error("Failed to record batch deletion in audit trail", [
                'error' => $e->getMessage(),
                'model' => $modelClass,
                'ids_count' => count($modelIds),
            ]);
            
            return null;
        }
    }
    
    /**
     * Record a cascading deletion (parent and all its dependencies)
     *
     * @param Model $parentModel The parent model being deleted
     * @param array $childRecords Array of child records organized by type
     * @param string $reason Optional reason for deletion
     * @return \App\Models\AuditTrail
     */
    public static function logCascadingDeletion(Model $parentModel, array $childRecords, string $reason = null)
    {
        try {
            DB::beginTransaction();
            
            $parentClass = get_class($parentModel);
            $parentName = class_basename($parentClass);
            $userId = Auth::id();
            $userInfo = self::getUserInfo();
            
            // Prepare hierarchy information
            $hierarchy = [];
            $recordCount = 0;
            
            foreach ($childRecords as $type => $records) {
                $count = count($records);
                $recordCount += $count;
                $hierarchy[$type] = [
                    'count' => $count,
                    'ids' => array_map(function($record) {
                        return is_object($record) ? $record->getKey() : $record;
                    }, $records)
                ];
            }
            
            // Create comprehensive audit record
            $auditTrail = AuditTrail::create([
                'user_id' => $userId,
                'action' => 'cascade_delete',
                'model_type' => $parentClass,
                'model_name' => $parentName,
                'model_id' => $parentModel->getKey(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'reason' => $reason,
                'related_records' => $hierarchy,
                'additional_info' => [
                    'total_records_affected' => $recordCount + 1, // Parent + children
                    'parent_attributes' => $parentModel->getAttributes(),
                ],
                'user_info' => $userInfo,
                'timestamp' => now(),
            ]);
            
            DB::commit();
            
            Log::info("Cascading deletion audit trail recorded", [
                'audit_trail_id' => $auditTrail->id,
                'parent_model' => $parentName,
                'parent_id' => $parentModel->getKey(),
                'total_records' => $recordCount + 1,
                'user_id' => $userId,
            ]);
            
            return $auditTrail;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error("Failed to record cascading deletion in audit trail", [
                'error' => $e->getMessage(),
                'parent_model' => get_class($parentModel),
                'parent_id' => $parentModel->getKey(),
            ]);
            
            return null;
        }
    }
    
    /**
     * Format related records for storage in the audit trail
     *
     * @param array $relatedRecords Array of related records
     * @return array
     */
    protected static function formatRelatedRecords(array $relatedRecords)
    {
        $formatted = [];
        
        foreach ($relatedRecords as $type => $records) {
            // Handle both arrays of models and arrays of IDs
            if (is_array($records) || $records instanceof Collection) {
                $ids = [];
                $count = count($records);
                
                foreach ($records as $record) {
                    if (is_object($record) && method_exists($record, 'getKey')) {
                        $ids[] = $record->getKey();
                    } else {
                        $ids[] = $record;
                    }
                }
                
                $formatted[$type] = [
                    'count' => $count,
                    'ids' => $ids
                ];
            } else {
                // Handle single model or ID
                $id = is_object($records) && method_exists($records, 'getKey') ? $records->getKey() : $records;
                $formatted[$type] = [
                    'count' => 1,
                    'ids' => [$id]
                ];
            }
        }
        
        return $formatted;
    }
    
    /**
     * Get information about the current user for the audit trail
     *
     * @return array
     */
    protected static function getUserInfo()
    {
        $user = Auth::user();
        
        if (!$user) {
            return [
                'id' => null,
                'type' => 'system',
                'name' => 'System Process',
            ];
        }
        
        return [
            'id' => $user->id,
            'type' => 'user',
            'name' => $user->name,
            'email' => $user->email,
            'roles' => method_exists($user, 'getRoleNames') ? $user->getRoleNames() : [],
        ];
    }
    
    /**
     * Get a detailed list of all related entities for a model
     * 
     * @param Model $model The model to check relations for
     * @return array Associative array of related entities
     */
    public static function getRelatedRecords(Model $model)
    {
        $related = [];
        
        // Get all relationship methods on the model
        $relations = [];
        $reflection = new \ReflectionClass($model);
        
        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->class != get_class($model) || 
                $method->getNumberOfParameters() > 0 || 
                in_array($method->getName(), ['pivot', 'query', 'newQuery'])) {
                continue;
            }
            
            try {
                $return = $method->invoke($model);
                if ($return instanceof \Illuminate\Database\Eloquent\Relations\Relation) {
                    $relations[$method->getName()] = $return;
                }
            } catch (\Exception $e) {
                // Skip methods that don't return a relation
            }
        }
        
        // Collect related records
        foreach ($relations as $relationName => $relation) {
            $relatedRecords = $relation->get();
            if ($relatedRecords->isNotEmpty()) {
                $related[$relationName] = $relatedRecords->map(function($record) {
                    return [
                        'id' => $record->getKey(),
                        'type' => get_class($record),
                        'attributes' => $record->getAttributes(),
                    ];
                })->toArray();
            }
        }
        
        return $related;
    }
}