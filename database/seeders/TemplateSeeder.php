<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EmailTemplate;

class TemplateSeeder extends Seeder
{
    public function run(): void
    {
        EmailTemplate::create([
            'category' => 'hr',
            'name' => 'Employee Invitation',
            'description' => 'Welcome email sent to new employees with onboarding form link',
            'subject' => 'Welcome to CodeAge Private Limited - Complete Your Onboarding',
            'body' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
  <div style="background: linear-gradient(to right, #f97316, #dc2626); padding: 30px; text-align: center;">
    <h1 style="color: white; margin: 0;">Welcome to CodeAge!</h1>
  </div>
  
  <div style="padding: 30px; background: #f9fafb;">
    <p style="font-size: 16px; color: #374151;">Hello {{employeeName}},</p>
    
    <p style="font-size: 16px; color: #374151; line-height: 1.6;">
      We are excited to have you join our team at <strong>CodeAge Private Limited</strong>! 
      To complete your onboarding process, please fill out the employee information form.
    </p>
    
    <div style="text-align: center; margin: 30px 0;">
      <a href="{{formLink}}" style="background: linear-gradient(to right, #f97316, #dc2626); color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; display: inline-block; font-weight: bold;">
        Complete Onboarding Form
      </a>
    </div>
    
    <p style="font-size: 14px; color: #6b7280; line-height: 1.6;">
      This link is valid for one-time use only and will expire after submission. 
      If you have any questions, please contact our HR department.
    </p>
    
    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
      <p style="font-size: 14px; color: #6b7280; margin: 0;">
        Best regards,<br>
        <strong>CodeAge HR Team</strong>
      </p>
    </div>
  </div>
  
  <div style="background: #1f2937; padding: 20px; text-align: center;">
    <p style="color: #9ca3af; font-size: 12px; margin: 0;">
      © 2026 CodeAge Private Limited. All rights reserved.
    </p>
  </div>
</div>',
            'variables' => '{{employeeName}}, {{formLink}}',
            'is_active' => true,
        ]);
    }
}
