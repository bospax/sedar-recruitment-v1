<?php

namespace App\Http\Controllers;

use App\Exports\EmployeeDataExportError;
use App\Imports\DataSheetImport;
use App\Imports\EmployeeDataImport;
use Illuminate\Http\Request;

class EmployeeDataImportController extends Controller
{
    public function importEmployeeData(Request $request) 
    {
        $section = $request->input('current_section');
        $file = $request->file('import_employee');

        if ($request->hasFile('import_employee')) {
            $import = new DataSheetImport($section);
            $import->import($file);
            $failures = $import->getFailures();
            $filename = 'employeedata-errors.xlsx';

            if (!empty($failures)) {
                $failed_rows = [];

                foreach ($import->getErrors() as $failed_row) {
                    switch ($section) {
                        case 'general':
                            $failed_rows[] = [
                                $failed_row['prefix_id'],
                                $failed_row['id_number'],
                                $failed_row['first_name'],
                                $failed_row['middle_name'],
                                $failed_row['last_name'],
                                $failed_row['suffix'],
                                $failed_row['birthdate'],
                                $failed_row['religion'],
                                $failed_row['civil_status'],
                                $failed_row['gender'],
                                $failed_row['message']
                            ];
                            break;

                        case 'position':
                            $failed_rows[] = [
                                $failed_row['prefix_id'],
                                $failed_row['id_number'],
                                $failed_row['unit_code'],
                                // $failed_row['job_rate_code'],
                                $failed_row['division'],
                                $failed_row['division_category'],
                                $failed_row['company'],
                                $failed_row['location'],
                                $failed_row['schedule'],
                                $failed_row['tools'],
                                $failed_row['message']
                            ];
                            break;

                        case 'employment':
                            $failed_rows[] = [
                                $failed_row['prefix_id'],
                                $failed_row['id_number'],
                                $failed_row['employment_type'],
                                $failed_row['employment_date_start'],
                                $failed_row['employment_date_end'],
                                $failed_row['regularization_date'],
                                $failed_row['hired_date'],
                                $failed_row['message']
                            ];
                            break;

                        case 'status':
                            $failed_rows[] = [
                                $failed_row['prefix_id'],
                                $failed_row['id_number'],
                                $failed_row['employee_state'],
                                $failed_row['state_date_start'],
                                $failed_row['state_date_end'],
                                $failed_row['state_date'],
                                $failed_row['status_remarks'],
                                $failed_row['message']
                            ];
                            break;

                        case 'address':
                            $failed_rows[] = [
                                $failed_row['prefix_id'],
                                $failed_row['id_number'],
                                $failed_row['detailed_address'],
                                $failed_row['zip_code'],
                                $failed_row['message']
                            ];
                            break;

                        case 'attainment':
                            $failed_rows[] = [
                                $failed_row['prefix_id'],
                                $failed_row['id_number'],
                                $failed_row['attainment'],
                                $failed_row['course'],
                                $failed_row['degree'],
                                $failed_row['institution'],
                                $failed_row['honorary'],
                                $failed_row['gpa'],
                                $failed_row['message']
                            ];
                            break;

                        case 'account':
                            $failed_rows[] = [
                                $failed_row['prefix_id'],
                                $failed_row['id_number'],
                                $failed_row['sss_number'],
                                $failed_row['pagibig_number'],
                                $failed_row['philhealth_number'],
                                $failed_row['tin_number'],
                                $failed_row['bank_name'],
                                $failed_row['bank_account_number'],
                                $failed_row['message']
                            ];
                            break;

                        case 'contacts':
                            $failed_rows[] = [
                                $failed_row['prefix_id'],
                                $failed_row['id_number'],
                                $failed_row['contact_type'],
                                $failed_row['contact_details'],
                                $failed_row['message']
                            ];
                            break;
                        
                        default:
                            $failed_rows[] = [];
                            break;
                    }
                }

                $export_error = new EmployeeDataExportError($failed_rows, $section);
                $export_error->store('public/files/'.$filename);
            }

            return response()->json([
                'status' => 'imported',
                'message' => 'successfully imported',
                'errors' => $failures,
                'link' => '/storage/files/'.$filename
            ]);
        } else {
            return response()->json([
                'status' => 'failed',
                'message' => 'file is required'
            ]);
        }
    }
}
