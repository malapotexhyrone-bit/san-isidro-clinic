<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Appointment Confirmation</title>
</head>
<body style="margin:0;background:#f7f7f8;font-family:Arial,sans-serif;color:#25262a">
<div style="max-width:620px;margin:0 auto;padding:28px 18px">
    <div style="background:#ffffff;border:1px solid #e7e8eb;border-radius:8px;overflow:hidden">
        <div style="padding:22px 26px;background:#ff3157;color:#ffffff">
            <strong style="font-size:20px">San Isidro Family Medical Clinic</strong>
        </div>
        <div style="padding:26px">
            <p>Dear {{ $appointment->patient->full_name }},</p>
            <p>Your appointment at <strong>San Isidro Family Medical Clinic</strong> has been successfully scheduled.</p>

            <table role="presentation" style="width:100%;border-collapse:collapse;margin:22px 0">
                <tr><td style="padding:8px;border-bottom:1px solid #e7e8eb;color:#73777f">Patient Name</td><td style="padding:8px;border-bottom:1px solid #e7e8eb"><strong>{{ $appointment->patient->full_name }}</strong></td></tr>
                <tr><td style="padding:8px;border-bottom:1px solid #e7e8eb;color:#73777f">Patient ID</td><td style="padding:8px;border-bottom:1px solid #e7e8eb"><strong>{{ $appointment->patient->patient_number }}</strong></td></tr>
                <tr><td style="padding:8px;border-bottom:1px solid #e7e8eb;color:#73777f">Appointment ID</td><td style="padding:8px;border-bottom:1px solid #e7e8eb"><strong>{{ $appointment->appointment_number }}</strong></td></tr>
                <tr><td style="padding:8px;border-bottom:1px solid #e7e8eb;color:#73777f">Physician</td><td style="padding:8px;border-bottom:1px solid #e7e8eb"><strong>{{ $appointment->physician->name }}</strong></td></tr>
                <tr><td style="padding:8px;border-bottom:1px solid #e7e8eb;color:#73777f">Appointment Date</td><td style="padding:8px;border-bottom:1px solid #e7e8eb"><strong>{{ $appointment->appointment_date->format('F j, Y') }}</strong></td></tr>
                <tr><td style="padding:8px;border-bottom:1px solid #e7e8eb;color:#73777f">Appointment Time</td><td style="padding:8px;border-bottom:1px solid #e7e8eb"><strong>{{ date('g:i A', strtotime($appointment->appointment_time)) }}</strong></td></tr>
            </table>

            <div style="padding:18px;text-align:center;background:#fff0f3;border:1px solid #ffc3cf;border-radius:7px">
                <div style="font-size:12px;color:#73777f;text-transform:uppercase;letter-spacing:.08em">Verification Code</div>
                <div style="font-size:28px;font-weight:bold;letter-spacing:.12em;color:#d91f43;margin-top:7px">{{ $appointment->verification_code }}</div>
            </div>

            <p style="margin-top:22px">Please keep this email and present your verification code to the receptionist when you arrive. The receptionist will verify the code before marking your appointment as Arrived.</p>
            <p>If you need to reschedule or cancel your appointment, please contact San Isidro Family Medical Clinic.</p>
            <p>Thank you,<br><strong>San Isidro Family Medical Clinic</strong><br>Appointment and Patient Record Management System</p>
            <p style="font-size:12px;color:#73777f">This is an automated appointment confirmation. Please do not reply to this email.</p>
        </div>
    </div>
</div>
</body>
</html>
