<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\AnnualEvaluationController;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\AttainmentController;
use App\Http\Controllers\AttainmentsImportController;
use App\Http\Controllers\BankController;
use App\Http\Controllers\BanksImportController;
use App\Http\Controllers\CompaniesImportController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CoursesImportController;
use App\Http\Controllers\DaEvaluationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DegreeController;
use App\Http\Controllers\DegreesImportController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DepartmentsImportController;
use App\Http\Controllers\DivisionCategoriesImportController;
use App\Http\Controllers\DivisionCategoryController;
use App\Http\Controllers\DivisionController;
use App\Http\Controllers\DivisionsImportController;
use App\Http\Controllers\EmployeeAccountController;
use App\Http\Controllers\EmployeeAttainmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeDataChangeController;
use App\Http\Controllers\EmployeeDataImportController;
use App\Http\Controllers\EmployeeFileController;
use App\Http\Controllers\EmployeeMasterlistController;
use App\Http\Controllers\EmployeePositionController;
use App\Http\Controllers\EmployeeProfileController;
use App\Http\Controllers\EmployeesImportController;
use App\Http\Controllers\EmployeeStateController;
use App\Http\Controllers\EmployeeStatusController;
use App\Http\Controllers\FileTypeController;
use App\Http\Controllers\FileTypesImportController;
use App\Http\Controllers\FormApproverController;
use App\Http\Controllers\FormArchiveController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\FormFilingController;
use App\Http\Controllers\FormRequestController;
use App\Http\Controllers\FormSettingController;
use App\Http\Controllers\FormUpdateController;
use App\Http\Controllers\HonorariesImportController;
use App\Http\Controllers\HonoraryController;
use App\Http\Controllers\JobBandController;
use App\Http\Controllers\JobBandsImportController;
use App\Http\Controllers\JobHistoryController;
use App\Http\Controllers\JobRateController;
use App\Http\Controllers\JobRatesImportController;
use App\Http\Controllers\KPIController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\LocationsImportController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ManpowerController;
use App\Http\Controllers\MasterlistController;
use App\Http\Controllers\MeritIncreaseController;
use App\Http\Controllers\MonthlyEvaluationController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\PositionsImportController;
use App\Http\Controllers\PrintController;
use App\Http\Controllers\ProbiEvaluationController;
use App\Http\Controllers\ReceiverController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StaticDataController;
use App\Http\Controllers\StaticMasterlistController;
use App\Http\Controllers\SubunitController;
use App\Http\Controllers\SubunitsImportController;
use App\Http\Controllers\TitleController;
use App\Http\Controllers\TitlesImportController;
use App\Http\Controllers\TokenController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UsersImportController;
use App\Http\Resources\Employee;
use App\Http\Resources\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::middleware(['auth'])->group(function () {
    Route::get('locations/fetchlocations', [LocationController::class, 'getLocations']);
    Route::get('locations/sort', [LocationController::class, 'sortData']);
    Route::get('locations/export', [LocationController::class, 'export']);
    Route::get('locations/export/{daterange?}', [LocationController::class, 'exportByDate'])->where(['daterange' => '(.*)']);
    Route::post('locations/import', [LocationsImportController::class, 'store'])->name('locations/import');
    Route::resource('locations', LocationController::class);
});

Route::middleware(['auth'])->group(function () {
    Route::get('static_masterlists/sort', [StaticMasterlistController::class, 'sortData']);
    Route::resource('static_masterlists', StaticMasterlistController::class);
});

Route::middleware(['auth'])->group(function () {
    Route::get('titles/fetchtitles', [TitleController::class, 'getTitles']);
    Route::get('titles/sort', [TitleController::class, 'sortData']);
    Route::get('titles/export', [TitleController::class, 'export']);
    Route::get('titles/export/{daterange?}', [TitleController::class, 'exportByDate'])->where(['daterange' => '(.*)']);
    Route::post('titles/import', [TitlesImportController::class, 'store'])->name('titles/import');
    Route::resource('titles', TitleController::class);
});

Route::middleware(['auth'])->group(function () {
    Route::get('companies/fetchcompanies', [CompanyController::class, 'getCompanies']);
    Route::get('companies/sort', [CompanyController::class, 'sortData']);
    Route::get('companies/export', [CompanyController::class, 'export']);
    Route::get('companies/export/{daterange?}', [CompanyController::class, 'exportByDate'])->where(['daterange' => '(.*)']);
    Route::post('companies/import', [CompaniesImportController::class, 'store'])->name('companies/import');
    Route::resource('companies', CompanyController::class);
});

Route::middleware(['auth'])->group(function () {
    Route::get('divisions/fetchdivisions', [DivisionController::class, 'getDivisions']);
    Route::get('divisions/sort', [DivisionController::class, 'sortData']);
    Route::get('divisions/export', [DivisionController::class, 'export']);
    Route::get('divisions/export/{daterange?}', [DivisionController::class, 'exportByDate'])->where(['daterange' => '(.*)']);
    Route::post('divisions/import', [DivisionsImportController::class, 'store'])->name('divisions/import');
    Route::resource('divisions', DivisionController::class);
});

Route::middleware(['auth'])->group(function () {
    Route::get('filetypes/sort', [FileTypeController::class, 'sortData']);
    Route::get('filetypes/export', [FileTypeController::class, 'export']);
    Route::get('filetypes/export/{daterange?}', [FileTypeController::class, 'exportByDate'])->where(['daterange' => '(.*)']);
    Route::post('filetypes/import', [FileTypesImportController::class, 'store'])->name('filetypes/import');
    Route::resource('filetypes', FileTypeController::class);
});

Route::middleware(['auth'])->group(function () {
    Route::get('division_categories/getcategories', [DivisionCategoryController::class, 'getCategories']);
    Route::get('division_categories/sort', [DivisionCategoryController::class, 'sortData']);
    Route::get('division_categories/export', [DivisionCategoryController::class, 'export']);
    Route::get('division_categories/export/{daterange?}', [DivisionCategoryController::class, 'exportByDate'])->where(['daterange' => '(.*)']);
    Route::post('division_categories/import', [DivisionCategoriesImportController::class, 'store'])->name('division_categories/import');
    Route::resource('division_categories', DivisionCategoryController::class);
});

Route::middleware(['auth'])->group(function () {
    Route::get('banks/sort', [BankController::class, 'sortData']);
    Route::get('banks/export', [BankController::class, 'export']);
    Route::get('banks/export/{daterange?}', [BankController::class, 'exportByDate'])->where(['daterange' => '(.*)']);
    Route::post('banks/import', [BanksImportController::class, 'store'])->name('banks/import');
    Route::resource('banks', BankController::class);
});

Route::middleware(['auth'])->group(function () {
    Route::get('jobbands/fetchjobbands', [JobBandController::class, 'getJobBands']);
    Route::put('jobbands/order', [JobBandController::class, 'changeOrder']);
    Route::get('jobbands/sort', [JobBandController::class, 'sortData']);
    Route::get('jobbands/export', [JobBandController::class, 'export']);
    Route::get('jobbands/export/{daterange?}', [JobBandController::class, 'exportByDate'])->where(['daterange' => '(.*)']);
    Route::post('jobbands/import', [JobBandsImportController::class, 'store'])->name('jobbands/import');
    Route::resource('jobbands', JobBandController::class);
});

Route::middleware(['auth'])->group(function () {
    Route::get('attainments/sort', [AttainmentController::class, 'sortData']);
    Route::get('attainments/export', [AttainmentController::class, 'export']);
    Route::get('attainments/export/{daterange?}', [AttainmentController::class, 'exportByDate'])->where(['daterange' => '(.*)']);
    Route::post('attainments/import', [AttainmentsImportController::class, 'store'])->name('attainments/import');
    Route::resource('attainments', AttainmentController::class);
});

Route::middleware(['auth'])->group(function () {
    Route::get('courses/sort', [CourseController::class, 'sortData']);
    Route::get('courses/export', [CourseController::class, 'export']);
    Route::get('courses/export/{daterange?}', [CourseController::class, 'exportByDate'])->where(['daterange' => '(.*)']);
    Route::post('courses/import', [CoursesImportController::class, 'store'])->name('courses/import');
    Route::resource('courses', CourseController::class);
});

Route::middleware(['auth'])->group(function () {
    Route::get('degrees/sort', [DegreeController::class, 'sortData']);
    Route::get('degrees/export', [DegreeController::class, 'export']);
    Route::get('degrees/export/{daterange?}', [DegreeController::class, 'exportByDate'])->where(['daterange' => '(.*)']);
    Route::post('degrees/import', [DegreesImportController::class, 'store'])->name('degrees/import');
    Route::resource('degrees', DegreeController::class);
});

Route::middleware(['auth'])->group(function () {
    Route::get('honoraries/sort', [HonoraryController::class, 'sortData']);
    Route::get('honoraries/export', [HonoraryController::class, 'export']);
    Route::get('honoraries/export/{daterange?}', [HonoraryController::class, 'exportByDate'])->where(['daterange' => '(.*)']);
    Route::post('honoraries/import', [HonorariesImportController::class, 'store'])->name('honoraries/import');
    Route::resource('honoraries', HonoraryController::class);
});

Route::middleware(['auth'])->group(function () {
    Route::get('departments/fetchdepartment/{id}', [DepartmentController::class, 'getDepartment']);
    Route::get('departments/fetchdepartments', [DepartmentController::class, 'getDepartments']);
    Route::get('departments/sort', [DepartmentController::class, 'sortData']);
    Route::get('departments/export', [DepartmentController::class, 'export']);
    Route::get('departments/export/{daterange?}', [DepartmentController::class, 'exportByDate'])->where(['daterange' => '(.*)']);
    Route::post('departments/import', [DepartmentsImportController::class, 'store'])->name('departments/import');
    Route::resource('departments', DepartmentController::class);
});

Route::middleware(['auth'])->group(function () {
    Route::get('subunits/fetchallavailableforreceiver', [SubunitController::class, 'fetchAllAvailableSubunitReceiver']);
    Route::get('subunits/fetchallavailable', [SubunitController::class, 'fetchAllAvailableSubunit']);
    Route::get('subunits/fetchall', [SubunitController::class, 'fetchAllSubunit']);
    Route::get('subunits/fetch/{id}', [SubunitController::class, 'fetchSubunit']);
    Route::get('subunits/sort', [SubunitController::class, 'sortData']);
    Route::get('subunits/export', [SubunitController::class, 'export']);
    Route::get('subunits/export/{daterange?}', [SubunitController::class, 'exportByDate'])->where(['daterange' => '(.*)']);
    Route::post('subunits/import', [SubunitsImportController::class, 'store'])->name('subunits/import');
    Route::resource('subunits', SubunitController::class);
});

Route::middleware(['auth'])->group(function () {
    Route::get('jobrates/fetchjobrate/{position_id}', [JobRateController::class, 'getJobRate']);
    Route::get('jobrates/fetchjobrates', [JobRateController::class, 'getJobRates']);
    Route::get('jobrates/sort', [JobRateController::class, 'sortData']);
    Route::get('jobrates/export', [JobRateController::class, 'export']);
    Route::get('jobrates/export/{daterange?}', [JobRateController::class, 'exportByDate'])->where(['daterange' => '(.*)']);
    Route::post('jobrates/import', [JobRatesImportController::class, 'store'])->name('jobrates/import');
    Route::resource('jobrates', JobRateController::class);
});

Route::middleware(['auth'])->group(function () {
    Route::post('positions/filters/changesuperior', [PositionController::class, 'changeSuperior']);
    Route::get('positions/filters/unpaginated', [PositionController::class, 'getPositionsUnpaginated']);
    Route::get('positions/filters', [PositionController::class, 'getPositionsWithFilters']);
    Route::get('positions/filter/department', [PositionController::class, 'loadFilterDepartment']);
    Route::get('positions/filter/position', [PositionController::class, 'loadFilterPosition']);
    Route::get('positions/filter/subunit', [PositionController::class, 'loadFilterSubunit']);
    Route::get('positions/filter/location', [PositionController::class, 'loadFilterLocation']);
    Route::get('positions/filter/superior', [PositionController::class, 'loadFilterSuperior']);
    Route::get('positions/fetchpositions/kpi', [PositionController::class, 'getPositionsKPI']);
    Route::get('positions/fetchpositions', [PositionController::class, 'getPositions']);
    Route::get('positions/fetchsuperiors', [PositionController::class, 'getSuperiors']);
    Route::get('positions/fetchposition/{id}', [PositionController::class, 'getPosition']);
    Route::get('positions/sort', [PositionController::class, 'sortData']);
    Route::get('positions/export', [PositionController::class, 'export']);
    Route::get('positions/export/{daterange?}', [PositionController::class, 'exportByDate'])->where(['daterange' => '(.*)']);
    Route::post('positions/import', [PositionsImportController::class, 'store'])->name('positions/import');
    Route::resource('positions', PositionController::class);
});

Route::middleware(['auth'])->group(function () {
    Route::get('employees/fetchprobi', [EmployeeController::class, 'getProbi']);
    Route::get('employees/fetchmo', [EmployeeController::class, 'getMo']);
    Route::get('employees/fetchannual', [EmployeeController::class, 'getAnnual']);
});

Route::middleware(['auth'])->group(function () {
    Route::get('employees/reminder', [EmployeeController::class, 'reminderForEvaluation']);
    Route::get('employees/fetchemployee/generalinfo/{id}', [EmployeeController::class, 'getEmployeeGeneralInfo']);
    Route::get('employees/tenure/{id}', [EmployeeController::class, 'getEmployeeTenure']);
    Route::get('employees/fetchemployees', [EmployeeController::class, 'getEmployees']);
    Route::get('employees/fetchemployeeapprover', [EmployeeController::class, 'getEmployeesForApprover']);
    Route::get('employees/sort', [EmployeeController::class, 'sortData']);
    Route::get('employees/export', [EmployeeController::class, 'export']);
    Route::get('employees/export/{daterange?}', [EmployeeController::class, 'exportByDate'])->where(['daterange' => '(.*)']);
    Route::post('employees/import', [EmployeesImportController::class, 'store'])->name('employees/import');
    Route::resource('employees', EmployeeController::class);
});

Route::middleware(['auth'])->group(function () {
    Route::post('registration/validate/generalinfo/fullname', [RegistrationController::class, 'validateFullName']);
    Route::post('registration/validate/generalinfo', [RegistrationController::class, 'validateGeneralInfoSection']);
    Route::post('registration/validate/positioninfo', [RegistrationController::class, 'validatePositionSection']);
    Route::post('registration/validate/employmentstatus', [RegistrationController::class, 'validateEmploymentTypeSection']);
    Route::post('registration/validate/employeestate', [RegistrationController::class, 'validateEmployeeStateSection']);
    Route::post('registration/validate/employeeaddress', [RegistrationController::class, 'validateEmployeeAddressSection']);
    Route::post('registration/validate/attainmentinfo', [RegistrationController::class, 'validateAttainmentInfoSection']);
    Route::post('registration/validate/employeeaccount', [RegistrationController::class, 'validateEmployeeAccountSection']);
    Route::post('registration/confirm', [RegistrationController::class, 'confirmRegistration']);
    Route::get('registration/data/employee', [RegistrationController::class, 'registrationDetails']); 
    Route::get('registration/data/fetchemployees', [RegistrationController::class, 'fetchEmployees']);
    Route::put('registration/update/generalinfo/{id}', [RegistrationController::class, 'updateGeneralInfo']);
    Route::put('registration/update/positioninfo/{id}', [RegistrationController::class, 'updatePositionSection']);
    Route::put('registration/update/employmentstatus/{id}', [RegistrationController::class, 'updateEmploymentTypeSection']);
    Route::put('registration/update/employeestate/{id}', [RegistrationController::class, 'updateEmployeeStateSection']);
    Route::put('registration/update/employeeaddress/{id}', [RegistrationController::class, 'updateEmployeeAddressSection']);
    Route::put('registration/update/attainmentinfo/{id}', [RegistrationController::class, 'updateAttainmentInfoSection']);
    Route::put('registration/update/employeeaccount/{id}', [RegistrationController::class, 'updateEmployeeAccountSection']);
    Route::put('registration/update/files/{id}', [RegistrationController::class, 'updateFileSection']);
    Route::put('registration/update/contacts/{id}', [RegistrationController::class, 'updateContactSection']);
    Route::delete('registration/delete/record/{id}', [RegistrationController::class, 'deleteRecord']);
    Route::put('registration/resubmit/record/{id}', [RegistrationController::class, 'resubmitRegistration']);
    Route::resource('employeecontacts', ContactController::class);
    Route::get('employeecontacts/fetchcontactstable/{employee_id}', [ContactController::class, 'getContactsTable']);
});

Route::middleware(['auth'])->group(function () {
    Route::post('user/change', [UserController::class, 'changePassword']);
    Route::get('users/authenticated', [UserController::class, 'authenticated']);
});

Route::middleware(['auth'])->group(function () {
    Route::put('users/resetpassword', [UserController::class, 'resetPassword']);
    Route::put('users/changestatus', [UserController::class, 'changeUserStatus']);
    Route::get('users/sort', [UserController::class, 'sortData']);
    Route::get('users/export', [UserController::class, 'export']);
    Route::get('users/export/{daterange?}', [UserController::class, 'exportByDate'])->where(['daterange' => '(.*)']);
    Route::post('users/import', [UsersImportController::class, 'store'])->name('users/import');
    Route::resource('users', UserController::class);
});

Route::middleware(['auth'])->group(function () {
    Route::get('addresses/fetchregions', [AddressController::class, 'fetchRegions']);
    Route::get('addresses/fetchprovinces/{reg_code}', [AddressController::class, 'fetchProvinces']);
    Route::get('addresses/fetchmunicipals/{prov_code}', [AddressController::class, 'fetchMunicipals']);
    Route::get('addresses/fetchbarangays/{citymun_code}', [AddressController::class, 'fetchBarangays']);
    Route::get('addresses/fetchemployeeaddress/{id}', [AddressController::class, 'getEmployeeAddress']);
    Route::resource('addresses', AddressController::class);
});

Route::middleware(['auth'])->group(function () {
    Route::put('masterlist/changestatus', [MasterlistController::class, 'changeStatus']);
});

Route::middleware(['auth'])->group(function () {
    Route::put('employee_positions/withunits/{id}', [EmployeePositionController::class, 'updateWithUnits']);
    Route::resource('employee_positions', EmployeePositionController::class);
});

Route::middleware(['auth'])->group(function () {
    Route::resource('employee_attainments', EmployeeAttainmentController::class);
    Route::get('employee_attainments/employee/fetchattainment/{id}', [EmployeeAttainmentController::class, 'getEmployeeAttainment']);
});

Route::middleware(['auth'])->group(function () {
    Route::resource('employee_accounts', EmployeeAccountController::class);
    Route::get('employee_accounts/employee/fetchaccounts/{id}', [EmployeeAccountController::class, 'getEmployeeAccounts']);
});

Route::middleware(['auth'])->group(function () {
    Route::resource('form_settings', FormSettingController::class);
    Route::get('employee/fetchposition/{employee_id}', [EmployeePositionController::class, 'getEmployeePosition']);
});

Route::middleware(['auth'])->group(function () {
    Route::put('form_approvers/order', [FormApproverController::class, 'changeOrder']);
    Route::get('form_approvers/fetchapprovers/{form_setting_id}', [FormApproverController::class, 'fetchFormApprovers']);
    Route::resource('form_approvers', FormApproverController::class);
});

Route::middleware(['auth'])->group(function () {
    Route::get('employeeprofile/fetchposition/{employee_id}', [EmployeeProfileController::class, 'getPosition']);
    Route::get('employeeprofile/fetchaddresses/{employee_id}', [EmployeeProfileController::class, 'getAddresses']);
    Route::get('employeeprofile/fetchattainments/{employee_id}', [EmployeeProfileController::class, 'getAttainments']);
    Route::get('employeeprofile/fetchaccounts/{employee_id}', [EmployeeProfileController::class, 'getAccounts']);
    Route::get('employeeprofile/fetchemployees', [EmployeeProfileController::class, 'fetchProfiles']);
});

Route::middleware('throttle:1000,1')->group(function () {
    Route::get('token/generate', [TokenController::class, 'generateToken']);
    Route::resource('tokens', TokenController::class);

    Route::post('formrequests/confirmform', [FormRequestController::class, 'confirmForm']);
    Route::post('formrequests/reviewform', [FormRequestController::class, 'reviewForm']);
    Route::get('formrequests/fetchhistory', [FormRequestController::class, 'getHistory']);
    Route::get('formrequests/fetchrequests', [FormRequestController::class, 'getFormRequests']);
    Route::get('formrequests/fetchconfirmation', [FormRequestController::class, 'getFormConfirmation']);
    Route::get('formrequests/fetchrequestscount', [FormRequestController::class, 'getFormRequestCount']);
    Route::get('formrequests/fetchfilingscount', [FormRequestController::class, 'getFormFilingCount']);
    Route::get('formupdates/fetchupdatescount', [FormUpdateController::class, 'getFormUpdateCount']);
    Route::get('formrequests/fetchrejected', [FormRequestController::class, 'getFormRejected']);
    Route::get('formrequests/fetch-registration-request/{level}', [FormRequestController::class,'getRegistrationRequests']);
    Route::get('formrequests/fetch-manpower-request/{level}', [FormRequestController::class,'getManpowerRequests']);
    Route::put('formrequests/approve-registration', [FormRequestController::class, 'approveRegistration']);
    Route::put('formrequests/reject-registration', [FormRequestController::class, 'rejectRegistration']);
    Route::get('formrequests/form/approvers', [FormRequestController::class, 'getFormApprovers']);
    Route::get('formrequests/approver/forms', [FormRequestController::class, 'getApproverForms']);
    Route::get('formrequests/fetchhistory/approver', [FormRequestController::class, 'getApproverHistory']);
    Route::get('formrequests/list/approvers', [FormRequestController::class, 'getListOfApprovers']);

    Route::get('statuses/fetchdatehired/{employee_id}', [EmployeeStatusController::class, 'getDateHired']);
    Route::get('statuses/fetchstatus/{employee_id}', [EmployeeStatusController::class, 'getStatus']);
    Route::resource('statuses', EmployeeStatusController::class);

    Route::get('states/fetchstates/{employee_id}', [EmployeeStateController::class, 'getStatesWithAttachment']);
    Route::resource('states', EmployeeStateController::class);

    Route::get('employeefiles/fetchfilestable/{employee_id}', [EmployeeFileController::class, 'getFilesTable']);
    Route::get('employeefiles/fetchfiles/{employee_id}', [EmployeeFileController::class, 'getFiles']);
    Route::resource('employeefiles', EmployeeFileController::class);


    Route::post('forms/import', [FormController::class, 'importFormSetting']);
    Route::get('forms/fetchsubunits', [FormController::class, 'getSubunits']);
    Route::get('forms/fetchapprovers', [FormController::class, 'getApprovers']);
    Route::get('forms/updateform', [FormController::class, 'update']);
    Route::get('probi_evaluations/checkemployeestatus', [ProbiEvaluationController::class, 'checkEmployeeStatus']);
    Route::resource('probi_evaluations', ProbiEvaluationController::class);
    Route::resource('monthly_evaluations', MonthlyEvaluationController::class);
    Route::put('monthly_evaluation/resubmit/record/{id}', [MonthlyEvaluationController::class, 'resubmitMonthlyEvaluation']);
    Route::resource('annual_evaluations', AnnualEvaluationController::class);
    Route::resource('forms', FormController::class);

    Route::post('formfiling/validate/fileform', [FormFilingController::class, 'validateValidateDateRange']);
    Route::post('formfiling/fileform', [FormFilingController::class, 'fileForm']);
    Route::post('formfiling/filedatachangeform', [FormFilingController::class, 'fileDataChangeForm']);
    Route::get('formfiling/fetchfilings', [FormFilingController::class, 'getFormFilings']);
    Route::get('formfiling/fetchemployees', [FormFilingController::class, 'getEmployees']);
    Route::get('formfiling/fetchemployees/tobehired', [FormFilingController::class, 'getEmployeesTobeHired']);

    Route::get('formupdate/fetchupdates', [FormUpdateController::class, 'getFormUpdates']);
    Route::post('formupdate/fileform', [FormUpdateController::class, 'updateForm']);
    Route::post('formupdate/filedatachangeform', [FormUpdateController::class, 'updateDataChangeForm']);
    Route::post('formupdate/filemanpowerform', [FormUpdateController::class, 'updateManpowerForm']);

    Route::get('printing/manpower', [FormFilingController::class, 'getManpowerPrinting']);

    Route::get('receivers/fetchsubunits', [FormController::class, 'getSubunitsReceiver']);
    Route::get('receivers/updateform', [ReceiverController::class, 'update']);
    Route::resource('receivers', ReceiverController::class);

    Route::get('kpis/fetchkpis/{position_id}', [KPIController::class, 'getKPIs']);
    Route::post('kpis', [KPIController::class, 'store']);

    Route::get('roles/fetchroles', [RoleController::class, 'getRoles']);
    Route::resource('roles', RoleController::class);

    Route::get('dashboard/employees/count', [DashboardController::class, 'getEmployeeCount']);
    Route::get('dashboard/employees/agegroup', [DashboardController::class, 'getAgeGroup']);
    Route::get('dashboard/employees/gendergroup', [DashboardController::class, 'getGenderGroup']);
    Route::get('dashboard/employees/locationgroup', [DashboardController::class, 'getLocationGroup']);

    Route::get('employeeprofile/fetchemployeedata/{id}', [EmployeeProfileController::class, 'fetchPrintProfileData']);
    Route::get('forms/validate/approvers', [FormController::class, 'getApproverAndReceiver']);
    Route::get('formrequests/201/positions', [EmployeeDataChangeController::class, 'getPositions']);
    Route::get('formrequests/manpower', [ManpowerController::class, 'getPositions']);
    Route::get('formrequests/manpower/employees', [ManpowerController::class, 'getSubordinateEmployees']);
    Route::get('formrequests/manpower/positionchange', [ManpowerController::class, 'getPositionsChange']);
    Route::get('formrequests/manpower/employees/replacement', [ManpowerController::class, 'getEmployeeReplacement']);
    Route::get('formrequests/manpowers', [ManpowerController::class, 'getManpowers']);
    Route::get('formrequests/manpower/data', [ManpowerController::class, 'getManpowerData']);
    Route::get('formrequests/manpower/checkforms', [ManpowerController::class, 'checkForms']);
    Route::post('formrequests/manpower/validation', [ManpowerController::class, 'validateManpower']);
    Route::post('formrequests/manpower/store', [ManpowerController::class, 'storeManpower']);
    Route::post('formrequests/manpower/update', [ManpowerController::class, 'updateManpower']);
    Route::get('formhistory/details', [FormRequestController::class, 'getApproverFormHistory']);
    Route::delete('formrequests/manpower/delete/record/{id}', [ManpowerController::class, 'deleteManpower']);
    Route::post('formrequests/manpower/cancel/record/{id}', [ManpowerController::class, 'cancelManpower']);
    Route::post('formrequests/evaluation/cancel/record/{id}', [DaEvaluationController::class, 'cancelDAEvaluation']);
    Route::put('manpower/resubmit/record/{id}', [ManpowerController::class, 'resubmitManpower']);
    Route::post('manpower/formfiling/filemanpowerform', [FormFilingController::class, 'fileManpowerForm']);
    Route::get('evaluation/da/employees', [DaEvaluationController::class, 'getDAEmployees']);
    Route::resource('da_evaluations', DaEvaluationController::class);
    Route::resource('activitylogs', ActivityLogController::class);
    Route::put('evaluation/resubmit/record/{id}', [DaEvaluationController::class, 'resubmitEvaluation']);
    Route::get('formrequests/manpower/registration', [ManpowerController::class, 'getManpowerForRegistration']);

    Route::post('employee_datachange/cancel/record/{id}', [EmployeeDataChangeController::class, 'cancelDataChange']);
    Route::put('employee_datachange/resubmit/record/{id}', [EmployeeDataChangeController::class, 'resubmitDataChange']);

    Route::get('formrequests/datachange/employees', [MeritIncreaseController::class, 'getSubordinateEmployees']);
    Route::get('formrequests/datachange/jobrates', [MeritIncreaseController::class, 'getJobrates']);
    Route::get('formrequests/datachange/getmeritincrease', [MeritIncreaseController::class, 'getMeritIncrease']);
    Route::post('formrequests/datachange/store', [MeritIncreaseController::class, 'storeMeritIncrease']);
    Route::put('formrequests/datachange/update/{id}', [MeritIncreaseController::class, 'updateMeritIncrease']);
    Route::delete('formrequests/datachange/delete/{id}', [MeritIncreaseController::class, 'deleteMeritIncrease']);

    Route::get('employees/minified/fetchemployees/filtered', [EmployeeController::class, 'getEmployeesFiltered']);
    Route::get('employees/minified/fetchemployees', [EmployeeController::class, 'getEmployeesMinified']);
    Route::get('employees/minified/fetchemployeedetails', [EmployeeController::class, 'getEmployeeDetails']);

    Route::get('formrequests/201/employees', [EmployeeDataChangeController::class, 'getSubordinateEmployees']);
    Route::resource('employee_datachanges', EmployeeDataChangeController::class);
});

Route::middleware(['auth'])->group(function () {
    Route::get('formarchive/fetchemployees', [FormArchiveController::class, 'fetchEmployees']);
    Route::get('formarchive/generate', [FormArchiveController::class, 'generateArchive']);
    Route::get('formarchive/generate/da', [FormArchiveController::class, 'generateDAArchive']);
    Route::get('formarchive/generate/manpower', [FormArchiveController::class, 'generateArchiveManpower']);
    Route::get('formarchive/generate/manpower/unpaginated', [FormArchiveController::class, 'generateArchiveManpowerUnpaginated']);
    Route::get('formarchive/generate/report/manpower', [FormArchiveController::class, 'generateManpowerReport']);
    Route::get('formarchive/generate/datachange', [FormArchiveController::class, 'generateArchiveDataChange']);
});

Route::middleware('throttle:1000,1')->group(function () {
    Route::get('employees/masterlist/generalinfo', [EmployeeMasterlistController::class, 'getEmployeeGeneralInfo']);
    Route::get('employees/masterlist/positions', [EmployeeMasterlistController::class, 'getEmployeePosition']);
    Route::get('employees/masterlist/statuses', [EmployeeMasterlistController::class, 'getEmployeeStatus']);
    Route::get('employees/masterlist/states', [EmployeeMasterlistController::class, 'getEmployeeStates']);
    Route::get('employees/masterlist/attainments', [EmployeeMasterlistController::class, 'getEmployeeAttainment']);
    Route::get('employees/masterlist/accounts', [EmployeeMasterlistController::class, 'getEmployeeAccounts']);
    Route::get('employees/masterlist/address', [EmployeeMasterlistController::class, 'getEmployeeAddress']);
    Route::get('employees/masterlist/contacts', [EmployeeMasterlistController::class, 'getEmployeeContact']);
    Route::get('employees/masterlist/export', [EmployeeMasterlistController::class, 'export']);
    Route::get('employees/masterlist/export/date', [EmployeeMasterlistController::class, 'exportByDate']);
    Route::post('employees/masterlist/import', [EmployeeDataImportController::class, 'importEmployeeData']);
    Route::get('employee_history/employee/fetchhistory/{id}', [JobHistoryController::class, 'getJobHistory']);
    Route::get('employees/masterlist/manpowers', [EmployeeMasterlistController::class, 'getBindedManpower']);
});

Route::get('reports/generate', [ReportController::class, 'getReport']);

Route::post('/register', [RegisterController::class, 'register']);
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout']);

Route::middleware(['auth:sanctum', 'throttle:1000,1'])->group(function () {
    Route::get('data/employees', [ApiController::class, 'fetchEmployeeData']);
    Route::get('data/employee/{id}', [ApiController::class, 'fetchEmployeeData']);
    Route::get('data/employee/filter/idnumber', [ApiController::class, 'fetchEmployeeDataByNumber']);
    Route::get('data/locations', [ApiController::class, 'fetchLocationData']);
    Route::get('data/divisions', [ApiController::class, 'fetchDivisionData']);
    Route::get('data/departments', [ApiController::class, 'fetchDepartmentData']);
    Route::get('data/subunits', [ApiController::class, 'fetchSubunitData']);
    Route::get('data/positions', [ApiController::class, 'fetchPositionData']);
    Route::get('data/employee/filter/active', [ApiController::class, 'fetchEmployeeActive']);
    Route::get('data/employee/filter/inactive', [ApiController::class, 'fetchEmployeeInActive']);
});

Route::middleware(['auth'])->group(function () {
    Route::get('staticdata/courses', [StaticDataController::class, 'getCourses']);
    Route::get('staticdata/religions', [StaticDataController::class, 'getReligions']);
    Route::get('staticdata/schedules', [StaticDataController::class, 'getSchedules']);
    Route::get('staticdata/attainments', [StaticDataController::class, 'getAttainments']);
    Route::get('staticdata/filetypes', [StaticDataController::class, 'getFileTypes']);
    Route::get('staticdata/cabinets', [StaticDataController::class, 'getCabinets']);
    Route::get('staticdata/objectives', [StaticDataController::class, 'getObjectives']);
    Route::get('staticdata/teams', [StaticDataController::class, 'getTeams']);
    Route::get('staticdata/prefixes', [StaticDataController::class, 'getPrefixes']);
    Route::get('staticdata/staticlists', [StaticDataController::class, 'getStaticLists']);
});

Route::middleware(['auth'])->group(function () {
    Route::get('printing/monthly/{id}', [PrintController::class, 'printMonthly']);
    Route::get('printing/manpower/{id}', [PrintController::class, 'printManpower']);
    Route::get('printing/manpower/approver/{id}', [PrintController::class, 'getManpowerApprover']);
    Route::get('printing/manpower/receiver/{id}', [PrintController::class, 'getManpowerReceiver']);

    Route::get('printing/datachange/{id}', [PrintController::class, 'printDataChange']);
    Route::get('printing/datachange/approver/{id}', [PrintController::class, 'getDataChangeApprover']);
    Route::get('printing/datachange/receiver/{id}', [PrintController::class, 'getDataChangeReceiver']);

    Route::get('printing/monthly/approver/{id}', [PrintController::class, 'getMonthlyApprover']);
    Route::get('printing/monthly/receiver/{id}', [PrintController::class, 'getMonthlyReceiver']);
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('data/address/fetchregions', [ApiController::class, 'fetchRegions']);
    Route::get('data/address/fetchprovinces/{reg_code}', [ApiController::class, 'fetchProvinces']);
    Route::get('data/address/fetchmunicipals/{prov_code}', [ApiController::class, 'fetchMunicipals']);
    Route::get('data/address/fetchbarangays/{citymun_code}', [ApiController::class, 'fetchBarangays']);
    Route::get('data/address/fetchaddress', [ApiController::class, 'fetchAddress']);
});