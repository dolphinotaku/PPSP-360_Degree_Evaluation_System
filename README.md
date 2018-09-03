## Abstract
This is an assignment in my course, it is required to develop a system prototype that demostrates the core functionalities of the system, which is consistent with the software specification.<br />
The core of the system came from one of my project [AngularJS-CRUD-PHP](https://github.com/keithbox/AngularJS-CRUD-PHP) on GitHub, the assignment also as a playground for proof of concept.

## Project Purpose
360 Degree Evaluation System, a web based e-appraisal platform to evaluate an employee by the 360 degree feedback process. To review an employee performance from all around collaborator. It is a process to better understand how the employee is functioning as part of the team and to improve the ways team members work together. An employee will be assess by his/her supervisory, colleague, subordinate and self.

## Core Function
Project develop under Incremental Delivery model, the project separated into three phases, the core functions in incremental 1 were implemented.
- Incremental Delivery 1 
  - Master Data Maintenance
  - Questionnaire Design, Evaluation Entry
  - Evaluation Report
- Incremental Delivery 2
  - Generate User Account
  - Improve Evaluation Flexibiltiy
- Incremental Delivery 3
  - Email Notification
  - User Account Management

## Installation and Config
1. Database connection config, go to the web_root\model\config.php
2. Web client connection config, go to the web_root\js\config.js
   - serverHost: means the web server (php) domain
   - webRoot: means the web client domain, for redirect to the `[requireLoginPage | afterLoginPage]`
   - requireLoginPage: means the redirect destination for redirect a user visiting the page who without authentication
   - afterLoginPage: means the redirect destination for the valid user to the main page after logged in
3. Import the lasted `evaluation360.yyyymmdd` sql file to create sample data
4. Make sure you created and has access right on the location:
```
web_root\temp\export
web_root\temp\upload
```

## How to use
The system designed for three user type [admin | staff | vendor], and now admin user and general staff user are implemented.
- Staff user - for basic functions usage
  - Answer the questionnaire as a evaluator
  - Generate evaluation result in individual report (pdf), required all evaluator completed the evaluation
- Admin user - for HR/admin purpose
  - Import / export master data
  - Design questionnaire
  - Generate Evaluation Proposal, system assign who will be involved in the evaluation automatically
  - Evaluation tick off (may be a evaluation pre year or a evaluation pre half of year)
  - plus the staff user functions

admin login
```
id:admin
pwd:admin
```

before user login, please refer to the company hierarchy on documentation/orgnization structure.png

When admin performs Generate Evaluation Proposal, the following staffs will assign to do a evaluation for you
- you required to evaluate yourself
- your supervisor required to evaluate you if you have a supervisor
- your subordinate(s) required to evaluate you if you have

please add prefix `"gd00"` before the node showed in the [hierarchy](https://github.com/keithbox/PPSP-360_Degree_Evaluation_System/blob/master/documentation/Organization%20Structure.JPG), pwd same as the user name
for example, staff `gd00305` provided with questionnaire result and allowed to generate individual report
```
id: gd00305
pwd: gd00305
```

## Dependency - for generate individual report
- PHP 5.4 or above
- PHPExcel 1.8.1 (excel engine)
- mpdf 6.1.3 (pdf engine)
- OfficeToPdf.exe (pdf engine) [Requirement](https://officetopdf.codeplex.com/)

PHPExcel has integrate with dompdf, mpdf, tcpdf, in my working environment(windows), I recommended to use mpdf as the pdf engine, OfficeToPdf.

> - PHPExcel - generate the report in excel with chart
> - mpdf - for convert the excel to pdf format
> - OfficeToPdf - encrypt the pdf file
you need to pay some coding effort to fit for your different environment
