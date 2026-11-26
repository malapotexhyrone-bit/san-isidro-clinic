<?php

namespace Database\Seeders;

use App\Models\{Appointment,Consultation,Patient,Physician,PhysicianSchedule,User,Visit,VitalSign};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $password = Hash::make('Clinic123!');
        $receptionist = User::create(['name'=>'Elena Cruz','username'=>'reception','email'=>'reception@clinic.local','role'=>'Receptionist','is_active'=>true,'password'=>$password]);
        $nurse = User::create(['name'=>'Nina Aquino','username'=>'nurse','email'=>'nurse@clinic.local','role'=>'Nurse','is_active'=>true,'password'=>$password]);
        $physicianUser = User::create(['name'=>'Dr. Rafael Reyes','username'=>'physician','email'=>'physician@clinic.local','role'=>'Physician','is_active'=>true,'password'=>$password]);
        $secondPhysicianUser = User::create(['name'=>'Dr. Angela Lim','username'=>'physician2','email'=>'physician2@clinic.local','role'=>'Physician','is_active'=>true,'password'=>$password]);
        User::create(['name'=>'Clinic Administrator','username'=>'admin','email'=>'admin@clinic.local','role'=>'Administrator','is_active'=>true,'password'=>$password]);

        $reyes = Physician::create(['user_id'=>$physicianUser->id,'name'=>'Dr. Rafael Reyes','specialization'=>'Family Medicine','contact_number'=>'0917 555 0101','is_active'=>true]);
        $lim = Physician::create(['user_id'=>$secondPhysicianUser->id,'name'=>'Dr. Angela Lim','specialization'=>'General Practice','contact_number'=>'0917 555 0102','is_active'=>true]);
        foreach([$reyes,$lim] as $physician) foreach(range(1,6) as $day) PhysicianSchedule::create(['physician_id'=>$physician->id,'day_of_week'=>$day,'start_time'=>'08:00','end_time'=>'17:00','slot_duration'=>60,'is_available'=>true]);

        $patients = collect([
            ['P-00001','Maria','Santos','1990-04-22','Female','0917 100 1001','maria.santos@example.test','San Isidro, Nueva Ecija'],
            ['P-00002','Jose','Cruz','1985-01-10','Male','0917 100 1002','jose.cruz@example.test','San Roque, Nueva Ecija'],
            ['P-00003','Ana','Garcia','2001-06-14','Female','0917 100 1003','ana.garcia@example.test','Poblacion, Nueva Ecija'],
            ['P-00004','Luis','Ramos','1978-03-02','Male','0917 100 1004','luis.ramos@example.test','Malapit, Nueva Ecija'],
        ])->map(fn($p)=>Patient::create(['patient_number'=>$p[0],'first_name'=>$p[1],'last_name'=>$p[2],'birth_date'=>$p[3],'sex'=>$p[4],'contact_number'=>$p[5],'email'=>$p[6],'address'=>$p[7]]));

        $date = now()->isSunday() ? now()->addDay() : now();
        $appointment1 = Appointment::create(['appointment_number'=>'APT-'.$date->format('Ymd').'-00001','verification_code'=>'SIC-DEMO01','notification_email'=>$patients[0]->email,'verified_at'=>$date->copy()->setTime(8,54),'email_sent_at'=>$date->copy()->subDay(),'patient_id'=>$patients[0]->id,'physician_id'=>$reyes->id,'appointment_date'=>$date->toDateString(),'appointment_time'=>'09:00','reason'=>'Follow-up consultation','status'=>'Arrived','created_by'=>$receptionist->id]);
        Appointment::create(['appointment_number'=>'APT-'.$date->format('Ymd').'-00002','verification_code'=>'SIC-DEMO02','notification_email'=>$patients[1]->email,'email_sent_at'=>$date->copy()->subDay(),'patient_id'=>$patients[1]->id,'physician_id'=>$reyes->id,'appointment_date'=>$date->toDateString(),'appointment_time'=>'09:30','reason'=>'General consultation','status'=>'Scheduled','created_by'=>$receptionist->id]);
        Appointment::create(['appointment_number'=>'APT-'.$date->format('Ymd').'-00003','verification_code'=>'SIC-DEMO03','notification_email'=>$patients[2]->email,'email_sent_at'=>$date->copy()->subDay(),'patient_id'=>$patients[2]->id,'physician_id'=>$lim->id,'appointment_date'=>$date->toDateString(),'appointment_time'=>'10:00','reason'=>'Headache and fever','status'=>'Scheduled','created_by'=>$receptionist->id]);
        Appointment::create(['appointment_number'=>'APT-'.$date->copy()->subDay()->format('Ymd').'-00004','verification_code'=>'SIC-DEMO04','notification_email'=>$patients[3]->email,'email_sent_at'=>$date->copy()->subDays(2),'patient_id'=>$patients[3]->id,'physician_id'=>$reyes->id,'appointment_date'=>$date->copy()->subDay()->toDateString(),'appointment_time'=>'10:30','reason'=>'Routine checkup','status'=>'No-show','created_by'=>$receptionist->id]);

        $scheduledVisit = Visit::create(['visit_number'=>'VIS-'.$date->format('Ymd').'-00001','patient_id'=>$patients[0]->id,'appointment_id'=>$appointment1->id,'physician_id'=>$reyes->id,'arrived_at'=>$date->copy()->setTime(8,55),'visit_type'=>'Scheduled','status'=>'Vital Signs Recorded','reason'=>'Follow-up consultation','created_by'=>$receptionist->id]);
        VitalSign::create(['visit_id'=>$scheduledVisit->id,'blood_pressure'=>'120/80','temperature'=>36.7,'pulse'=>76,'respiratory_rate'=>18,'weight'=>58.5,'height'=>160,'notes'=>'Patient alert and ambulatory.','recorded_by'=>$nurse->id]);

        $oldVisit = Visit::create(['visit_number'=>'VIS-'.$date->copy()->subMonth()->format('Ymd').'-00002','patient_id'=>$patients[0]->id,'appointment_id'=>null,'physician_id'=>$reyes->id,'arrived_at'=>$date->copy()->subMonth()->setTime(10,15),'visit_type'=>'Walk-in','status'=>'Completed','reason'=>'Cough and colds','completed_at'=>$date->copy()->subMonth()->setTime(10,55),'created_by'=>$receptionist->id]);
        Consultation::create(['visit_id'=>$oldVisit->id,'physician_id'=>$reyes->id,'chief_complaint'=>'Cough for three days','assessment'=>'Acute upper respiratory infection','notes'=>'No respiratory distress.','recommendations'=>'Rest, fluids, and return if symptoms worsen.']);
    }
}
