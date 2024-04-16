<?php

use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/{any}', [PageController::class, 'index'])->where('any', '.*');
// Route::get('/', [PageController::class, 'index'])->name('pages.index');
// Route::get('/locations', [PageController::class, 'locations'])->name('pages.locations');
// Route::get('/divisions', [PageController::class, 'divisions'])->name('pages.divisions');
// Route::get('/companies', [PageController::class, 'companies'])->name('pages.companies');
// Route::get('/filetypes', [PageController::class, 'filetypes'])->name('pages.filetypes');
// Route::get('/attainments', [PageController::class, 'attainments'])->name('pages.attainments');
// Route::get('/courses', [PageController::class, 'courses'])->name('pages.courses');
// Route::get('/degrees', [PageController::class, 'degrees'])->name('pages.degrees');
// Route::get('/honoraries', [PageController::class, 'honoraries'])->name('pages.honoraries');
// Route::get('/division_categories', [PageController::class, 'division_categories'])->name('pages.division_categories');
// Route::get('/banks', [PageController::class, 'banks'])->name('pages.banks');
// Route::get('/jobbands', [PageController::class, 'jobbands'])->name('pages.jobbands');
// Route::get('/departments', [PageController::class, 'departments'])->name('pages.departments');
// Route::get('/subunits', [PageController::class, 'subunits'])->name('pages.subunits');
