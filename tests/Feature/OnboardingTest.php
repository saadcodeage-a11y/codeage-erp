<?php

namespace Tests\Feature;

use App\Models\Bank;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    private const ONE_PIXEL_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wn7L3sAAAAASUVORK5CYII=';

    public function test_invited_employee_can_submit_onboarding_form(): void
    {
        Storage::fake('public');

        $department = Department::create(['name' => 'Engineering']);
        $bank = Bank::create([
            'name' => 'Habib Bank',
            'code' => 'HBL',
            'is_active' => true,
        ]);

        $employee = Employee::create([
            'full_name' => 'Invited Employee',
            'email' => 'invitee@example.com',
            'department_id' => $department->id,
            'designation' => 'Developer',
            'status' => 'invited',
            'onboarding_token' => 'token-123',
        ]);

        $response = $this
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->withHeader('Accept', 'application/json')
            ->post(route('onboarding.submit', ['token' => $employee->onboarding_token]), [
                'full_name' => 'Invited Employee',
                'phone' => '03001234567',
                'cnic' => '1234567890123',
                'gender' => 'male',
                'dob' => '2000-01-01',
                'father_name' => 'Parent Name',
                'guardian_contact' => '03007654321',
                'current_address' => 'Current address',
                'permanent_address' => 'Permanent address',
                'education_level' => 'bachelors',
                'field_of_study' => 'Computer Science',
                'profile_picture' => UploadedFile::fake()->createWithContent('profile.png', base64_decode(self::ONE_PIXEL_PNG)),
                'cnic_front' => UploadedFile::fake()->create('cnic-front.pdf', 200, 'application/pdf'),
                'cnic_back' => UploadedFile::fake()->create('cnic-back.pdf', 200, 'application/pdf'),
                'cv' => UploadedFile::fake()->create('cv.pdf', 200, 'application/pdf'),
                'transcript' => UploadedFile::fake()->create('transcript.pdf', 200, 'application/pdf'),
                'has_bank_account' => '1',
                'bank_id' => $bank->id,
                'bank_account_title' => 'INVITED EMPLOYEE',
                'iban' => 'PK36SCBL0000001123456702',
                'policy_accepted' => '1',
                'signature' => 'data:image/png;base64,' . base64_encode('fake-signature'),
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'redirect_url' => route('onboarding.completed'),
        ]);

        $employee->refresh();

        $this->assertSame('pending_approval', $employee->status);
        $this->assertNotNull($employee->onboarding_completed_at);
        $this->assertSame($bank->id, $employee->bank_id);
        Storage::disk('public')->assertExists($employee->signature_path);
        Storage::disk('public')->assertExists($employee->profile_picture);
    }

    public function test_onboarding_validation_errors_are_returned_as_json(): void
    {
        $department = Department::create(['name' => 'Engineering']);

        $employee = Employee::create([
            'full_name' => 'Invited Employee',
            'email' => 'invitee@example.com',
            'department_id' => $department->id,
            'designation' => 'Developer',
            'status' => 'invited',
            'onboarding_token' => 'token-456',
        ]);

        $response = $this
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->withHeader('Accept', 'application/json')
            ->post(route('onboarding.submit', ['token' => $employee->onboarding_token]), []);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Please correct the highlighted errors and try again.',
        ]);
        $response->assertJsonValidationErrors([
            'full_name',
            'phone',
            'cnic',
            'profile_picture',
            'signature',
        ]);
    }
}
