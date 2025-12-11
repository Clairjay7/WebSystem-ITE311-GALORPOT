<?php

namespace App\Models;

use CodeIgniter\Model;

class MaterialModel extends Model
{
    protected $table = 'materials';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'course_id',
        'file_name',
        'file_path',
        'created_at',
        'updated_at',
        'deleted_at'
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted_at';

    /**
     * Insert a new material record
     * @param array $data - Array containing course_id, file_name, and file_path
     * @return int|bool - Returns the insert ID on success, false on failure
     */
    public function insertMaterial($data)
    {
        return $this->insert($data);
    }

    /**
     * Get all materials for a specific course
     * @param int $course_id - The course ID
     * @return array - Array of material records
     */
    public function getMaterialsByCourse($course_id)
    {
        return $this->where('course_id', $course_id)
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }

    /**
     * Get material by ID
     * @param int $material_id - The material ID
     * @return array|null - Material record or null if not found
     */
    public function getMaterialById($material_id)
    {
        return $this->find($material_id);
    }

    /**
     * Get deleted materials for a specific course
     * @param int $course_id - The course ID
     * @return array - Array of deleted material records
     */
    public function getDeletedMaterialsByCourse($course_id)
    {
        return $this->onlyDeleted()
                    ->where('course_id', $course_id)
                    ->orderBy('deleted_at', 'DESC')
                    ->findAll();
    }

    /**
     * Restore a deleted material
     * @param int $material_id - The material ID
     * @return bool - Returns true on success, false on failure
     */
    public function restoreMaterial($material_id)
    {
        return $this->onlyDeleted()
                    ->where('id', $material_id)
                    ->set('deleted_at', null)
                    ->update();
    }

    /**
     * Check if a file name already exists for a course (including deleted materials)
     * @param int $course_id - The course ID
     * @param string $file_name - The file name to check
     * @return bool - Returns true if file name exists, false otherwise
     */
    public function fileNameExists($course_id, $file_name)
    {
        // Check in active materials
        $activeMaterial = $this->where('course_id', $course_id)
                              ->where('file_name', $file_name)
                              ->first();
        
        if ($activeMaterial) {
            return true;
        }
        
        // Check in deleted materials
        $deletedMaterial = $this->onlyDeleted()
                                ->where('course_id', $course_id)
                                ->where('file_name', $file_name)
                                ->first();
        
        return $deletedMaterial !== null;
    }
}

