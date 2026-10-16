<?php

namespace Database\Seeders;

use App\Contact;
use App\System;
use App\Unit;
use App\User;
use App\Utils\BusinessUtil;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class YadgarSweetsSeeder extends Seeder
{
    /**
     * Base lookup tables that are not business data.
     *
     * @var array
     */
    private $keepTables = [
        'migrations',
        'currencies',
        'barcodes',
        'permissions',
        'system',
        'password_resets',
        'password_reset_tokens',
        'failed_jobs',
        'jobs',
        'job_batches',
        'cache',
        'cache_locks',
        'sessions',
    ];

    /**
     * Replace demo shops with one Yadgar Sweets business.
     *
     * @return void
     */
    public function run()
    {
        $this->clearBusinessData();

        $businessUtil = new BusinessUtil();
        $pkrId = DB::table('currencies')->where('code', 'PKR')->value('id');

        $user = User::create_user([
            'surname' => 'Mr',
            'first_name' => 'Admin',
            'last_name' => null,
            'username' => 'admin',
            'email' => 'admin@yadgarsweets.com',
            'password' => '123456',
            'language' => 'en',
        ]);

        $business = $businessUtil->createNewBusiness([
            'name' => 'Yadgar Sweets',
            'currency_id' => $pkrId,
            'start_date' => now()->toDateString(),
            'time_zone' => 'Asia/Karachi',
            'fy_start_month' => 7,
            'accounting_method' => 'fifo',
            'owner_id' => $user->id,
            'enabled_modules' => ['purchases', 'add_sale', 'pos_sale', 'stock_transfers', 'stock_adjustment', 'expenses'],
            'date_format' => 'd/m/Y',
            'time_format' => '24',
            'currency_symbol_placement' => 'before',
            'stop_selling_before' => 0,
            'weighing_scale_setting' => [],
            'pos_settings' => json_encode($businessUtil->defaultPosSettings()),
            'email_settings' => $businessUtil->defaultEmailSettings(),
            'sms_settings' => $businessUtil->defaultSmsSettings(),
        ]);

        $user->business_id = $business->id;
        $user->save();

        $businessUtil->newBusinessDefaultResources($business->id, $user->id);

        Unit::where('business_id', $business->id)->update([
            'actual_name' => 'Kilogram',
            'short_name' => 'Kg',
            'allow_decimal' => 1,
        ]);

        $location = $businessUtil->addLocation($business->id, [
            'name' => 'Yadgar Sweets',
            'country' => 'Pakistan',
            'state' => 'Sindh',
            'city' => 'Karachi',
            'zip_code' => '74000',
            'landmark' => 'Main Branch',
            'mobile' => '',
        ]);

        Permission::create([
            'name' => 'location.'.$location->id,
            'guard_name' => 'web',
        ]);

        $refCount = $businessUtil->setAndGetReferenceCount('contacts', $business->id);
        $contactId = $businessUtil->generateReferenceNumber('contacts', $refCount, $business->id);

        Contact::create([
            'business_id' => $business->id,
            'type' => 'supplier',
            'supplier_business_name' => 'Main Supplier',
            'name' => 'Main Supplier',
            'first_name' => 'Main Supplier',
            'mobile' => '',
            'country' => 'Pakistan',
            'created_by' => $user->id,
            'is_default' => 0,
            'contact_id' => $contactId,
            'credit_limit' => 0,
        ]);

        $this->syncSystemSettings($pkrId);
    }

    /**
     * Remove demo businesses, users, products, and sales.
     *
     * @return void
     */
    private function clearBusinessData()
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $database = DB::getDatabaseName();
        $key = 'Tables_in_'.$database;

        foreach (DB::select('SHOW TABLES') as $table) {
            $name = $table->{$key};
            if (! in_array($name, $this->keepTables, true)) {
                DB::table($name)->truncate();
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        DB::table('permissions')->where('name', 'like', 'location.%')->delete();
    }

    /**
     * Point invoice and currency settings at Yadgar Sweets / PKR.
     *
     * @param  int  $pkrId
     * @return void
     */
    private function syncSystemSettings($pkrId)
    {
        $settings = [
            'app_currency_id' => $pkrId,
            'invoice_business_name' => 'Yadgar Sweets',
            'invoice_business_landmark' => 'Main Branch',
            'invoice_business_zip' => '74000',
            'invoice_business_state' => 'Sindh',
            'invoice_business_city' => 'Karachi',
            'invoice_business_country' => 'Pakistan',
            'email' => '',
        ];

        foreach ($settings as $key => $value) {
            System::addProperty($key, $value);
        }
    }
}
