<?php

namespace App\Controllers;

use App\Application\Services\ReportingService;
use App\Application\Services\XlsxReportService;

class ReportsController extends BaseController
{
    private function input(): array
    {
        $type = (string) $this->request->getGet('type');
        if (! in_array($type, ['inventory', 'available', 'borrowed', 'overdue', 'damaged', 'maintenance', 'history'], true)) {
            $type = 'inventory';
        }

        return [$type, [
            'from' => (string) $this->request->getGet('from'),
            'to' => (string) $this->request->getGet('to'),
            'category_id' => (int) $this->request->getGet('category_id'),
            'status' => (string) $this->request->getGet('status'),
            'borrower_id' => (int) $this->request->getGet('borrower_id'),
            'equipment_id' => (int) $this->request->getGet('equipment_id'),
        ]];
    }

    public function index()
    {
        $this->refreshOverdueSafely();
        [$type, $filters] = $this->input();

        return view('reports/index', [
            'title' => 'Reports',
            'type' => $type,
            'filters' => $filters,
            'rows' => (new ReportingService())->report($type, $filters),
            'categories' => $this->repository()->categories(true),
            'equipment' => $this->repository()->equipment(),
            'borrowers' => db_connect()->table('users')->select('id,display_name')->where(['role' => 'borrower', 'status' => 'active'])->orderBy('display_name')->get()->getResultArray(),
        ]);
    }

    public function print()
    {
        $this->refreshOverdueSafely();
        [$type, $filters] = $this->input();

        return view('reports/print', [
            'title' => 'BantayGamit Report',
            'type' => $type,
            'filters' => $filters,
            'rows' => (new ReportingService())->report($type, $filters),
        ]);
    }

    public function xlsx()
    {
        $this->refreshOverdueSafely();
        [$type, $filters] = $this->input();

        try {
            $rows = (new ReportingService())->report($type, $filters);
            $export = (new XlsxReportService())->create($type, $filters, $rows);
            return $this->response->download($export['filename'], $export['content'], true);
        } catch (\Throwable $e) {
            $query = http_build_query(array_merge(['type' => $type], $filters));
            return redirect()->to('/reports?' . $query)->with('error', $this->safeErrorMessage($e, 'Excel report could not be generated.'));
        }
    }

    private function refreshOverdueSafely(): void
    {
        try {
            $this->borrowingService()->refreshOverdue();
        } catch (\Throwable) {
        }
    }
}
