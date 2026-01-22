<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EmailTemplate;

class WelcomeEmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        EmailTemplate::create([
            'category' => 'hr',
            'name' => 'Employee Welcome',
            'description' => 'Professional welcome email sent after HR approval with first-day details',
            'subject' => 'Welcome Aboard! Your First Day Information',
            'body' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
  <div style="background: linear-gradient(to right, #f97316, #dc2626); padding: 30px; text-align: center;">
    <h1 style="color: white; margin: 0;">Welcome to CodeAge!</h1>
  </div>
  
  <div style="padding: 30px; background: #f9fafb;">
    <p style="font-size: 16px; color: #374151;">Dear {{employeeName}},</p>
    
    <p style="font-size: 16px; color: #374151; line-height: 1.6;">
      Congratulations and welcome to the <strong>CodeAge Private Limited</strong> team! 
      We\'re thrilled to have you joining us as <strong>{{position}}</strong>.
    </p>
    
    <div style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #f97316;">
      <h3 style="margin-top: 0; color: #374151;">First Day Details</h3>
      <p style="margin: 5px 0; color: #374151;"><strong>Start Date:</strong> {{startDate}}</p>
      <p style="margin: 5px 0; color: #374151;"><strong>Time:</strong> {{startTime}}</p>
      <p style="margin: 5px 0; color: #374151;"><strong>Location:</strong> {{officeLocation}}</p>
      <p style="margin: 5px 0; color: #374151;"><strong>Contact Person:</strong> {{hrContact}}</p>
    </div>
    
    <h3 style="color: #374151;">What to Bring:</h3>
    <ul style="color: #374151; line-height: 1.8;">
      <li>Valid ID (CNIC/Passport)</li>
      <li>Educational certificates (original + photocopies)</li>
      <li>Passport-sized photographs (2 copies)</li>
      <li>Previous employment documents (if applicable)</li>
    </ul>
    
    <p style="font-size: 14px; color: #6b7280; line-height: 1.6;">
      We look forward to seeing you on your first day! If you have any questions, 
      please don\'t hesitate to reach out to our HR team.
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
            'variables' => '{{employeeName}}, {{position}}, {{startDate}}, {{startTime}}, {{officeLocation}}, {{hrContact}}',
            'is_active' => true,
        ]);
    }
}
