<?php

declare(strict_types=1);

namespace App\Services\Recording\Contracts;

use App\Models\RecordingSale;
use Illuminate\Support\Collection;
use App\Services\Recording\DTOs\ServiceResult;

interface RecordingSaleServiceInterface
{
    /**
     * Create a new recording sale (draft/temporary).
     * @param array $data
     * @return ServiceResult
     */
    public function create(array $data): ServiceResult;

    /**
     * Update an existing recording sale.
     * @param string $id
     * @param array $data
     * @return ServiceResult
     */
    public function update(string $id, array $data): ServiceResult;

    /**
     * Delete a recording sale (soft delete).
     * @param string $id
     * @return ServiceResult
     */
    public function delete(string $id): ServiceResult;

    /**
     * Get a single recording sale by ID.
     * @param string $id
     * @return ServiceResult
     */
    public function getById(string $id): ServiceResult;

    /**
     * List all recording sales for a livestock and date (optionally filtered by status).
     * @param string $livestockId
     * @param string $date
     * @param array $filters
     * @return ServiceResult
     */
    public function listByLivestockAndDate(string $livestockId, string $date, array $filters = []): ServiceResult;

    /**
     * List all recording sales for a company in a date range (optionally filtered).
     * @param string $companyId
     * @param string $startDate
     * @param string $endDate
     * @param array $filters
     * @return ServiceResult
     */
    public function listByCompanyAndPeriod(string $companyId, string $startDate, string $endDate, array $filters = []): ServiceResult;

    /**
     * Validate a recording sale (business rules, completeness, etc).
     * @param array $data
     * @return ServiceResult
     */
    public function validate(array $data): ServiceResult;

    /**
     * Finalize a recording sale (move to valid sales, mark as finalized, etc).
     * @param string $id
     * @return ServiceResult
     */
    public function finalize(string $id): ServiceResult;
}
