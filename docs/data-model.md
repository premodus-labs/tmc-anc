# TMC Clinic Data Model

Core clinical management system with the antenatal care extension, drawn from the PostgreSQL schema after it loaded and passed a full patient-journey test.

48 tables374 columns89 foreign keys6 reporting viewsPostgreSQL 16

[Overview](#overview) [Staff & organisation](#staff) [Access control](#access) [Patients](#patients) [Visit flow & queue](#visits) [Clinical](#clinical) [Billing & insurance claims](#billing) [ANC extension](#anc)

## Overview

core path

How a visit connects the patient, the clinical work, the bill and the ANC records. Attributes are in the sections below.

**||--o{** one to many

**||--o|** one to one (unique FK)

**|o--o{** optional parent (nullable FK)

**\*\_by** columns reference `app_user`; those lines are drawn only under Access control to keep diagrams readable

```mermaid
%%{init: {'theme':'base','themeVariables':{'primaryColor':'#ffffff','primaryBorderColor':'#2f5d62','primaryTextColor':'#1d2b2c','lineColor':'#2f5d62','tertiaryColor':'#eef4f3','fontFamily':'Public Sans, Helvetica, Arial, sans-serif','fontSize':'14px'},'er':{'useMaxWidth':false}}}%%
erDiagram
    patient ||--o{ patient_insurance : "patient_id"
    insurance_provider ||--o{ patient_insurance : "provider_id"
    patient ||--o{ visitation : "patient_id"
    patient_insurance |o--o{ visitation : "patient_insurance_id"
    checkpoint |o--o{ visitation : "current_checkpoint_id"
    visitation ||--o{ visitation_checkpoint_log : "visitation_id"
    checkpoint ||--o{ visitation_checkpoint_log : "checkpoint_id"
    visitation ||--o{ visitation_vitals : "visitation_id"
    visitation ||--o{ consultation : "visitation_id"
    app_user ||--o{ consultation : "clinician_id"
    visitation ||--o{ lab_order : "visitation_id"
    consultation ||--o{ lab_order : "consultation_id"
    lab_order ||--o| lab_result : "lab_order_id"
    visitation ||--o{ prescription : "visitation_id"
    consultation ||--o{ prescription : "consultation_id"
    prescription ||--o{ prescription_item : "prescription_id"
    medication ||--o{ prescription_item : "medication_id"
    visitation ||--o| bill : "visitation_id"
    patient_insurance |o--o{ bill : "patient_insurance_id"
    bill ||--o{ bill_item : "bill_id"
    lab_order |o--o| bill_item : "lab_order_id"
    prescription_item |o--o| bill_item : "prescription_item_id"
    bill ||--o{ payment : "bill_id"
    bill ||--o{ insurance_claim : "bill_id"
    patient_insurance ||--o{ insurance_claim : "patient_insurance_id"
    insurance_provider ||--o{ insurance_claim : "provider_id"
    patient ||--o{ pregnancy : "patient_id"
    visitation ||--o| anc_visit : "visitation_id"
    pregnancy ||--o{ anc_visit : "pregnancy_id"
    patient ||--o{ appointment : "patient_id"
    pregnancy |o--o{ appointment : "pregnancy_id"
    specialist_session |o--o{ appointment : "specialist_session_id"
    visitation |o--o| appointment : "visitation_id"
    staff ||--o{ specialist_session : "staff_id"
    staff ||--o| app_user : "staff_id"
    patient
    patient_insurance
    insurance_provider
    visitation
    checkpoint
    visitation_checkpoint_log
    visitation_vitals
    consultation
    lab_order
    lab_result
    prescription
    prescription_item
    medication
    bill
    bill_item
    payment
    insurance_claim
    pregnancy
    anc_visit
    appointment
    specialist_session
    staff
    app_user
```

## Staff & organisation

10 tables

Employees, visiting specialists, HR records and rosters. Visiting ANC specialists are staff with employment type `visiting`.

Links out to: `"position"` (shown with their key only)

```mermaid
%%{init: {'theme':'base','themeVariables':{'primaryColor':'#ffffff','primaryBorderColor':'#2f5d62','primaryTextColor':'#1d2b2c','lineColor':'#2f5d62','tertiaryColor':'#eef4f3','fontFamily':'Public Sans, Helvetica, Arial, sans-serif','fontSize':'14px'},'er':{'useMaxWidth':false}}}%%
erDiagram
    department {
        bigint id PK
        varchar name UK
        varchar abbreviation "null"
    }
    position {
        bigint id
        varchar name
        varchar abbreviation "null"
    }
    staff {
        bigint id PK
        varchar employee_number UK
        varchar first_name
        varchar last_name
        text sex "null"
        date date_of_birth "null"
        varchar national_id UK "null"
        text marital_status "null"
        text residential_address "null"
        bigint position_id FK "null"
        bigint department_id FK "null"
        text employment_type
        text employment_status
        date hire_date "null"
        date termination_date "null"
        timestamptz created_at
        timestamptz updated_at
    }
    staff_phone {
        bigint id PK
        bigint staff_id FK
        varchar phone_number
        bool is_primary
    }
    staff_next_of_kin {
        bigint id PK
        bigint staff_id FK
        varchar first_name
        varchar last_name
        varchar relationship "null"
        varchar phone_number "null"
    }
    staff_qualification {
        bigint id PK
        bigint staff_id FK
        varchar title
        varchar institution "null"
        smallint year_obtained "null"
    }
    staff_professional_registration {
        bigint id PK
        bigint staff_id FK
        varchar regulatory_body UK
        varchar registration_number UK
        date valid_until "null"
    }
    staff_contract {
        bigint id PK
        bigint staff_id FK
        text contract_type
        numeric pay_rate
        text pay_basis
        date start_date
        date end_date "null"
    }
    staff_payment_account {
        bigint id PK
        bigint staff_id FK
        text method
        varchar provider "null"
        varchar account_number
        varchar account_name
        bool is_primary
    }
    staff_shift {
        bigint id PK
        bigint staff_id FK
        bigint department_id FK "null"
        date shift_date
        time start_time
        time end_time
        text notes "null"
    }
    "position" {
        bigint id PK
    }
    "position" |o--o{ staff : "position_id"
    department |o--o{ staff : "department_id"
    staff ||--o{ staff_phone : "staff_id"
    staff ||--o{ staff_next_of_kin : "staff_id"
    staff ||--o{ staff_qualification : "staff_id"
    staff ||--o{ staff_professional_registration : "staff_id"
    staff ||--o{ staff_contract : "staff_id"
    staff ||--o{ staff_payment_account : "staff_id"
    staff ||--o{ staff_shift : "staff_id"
    department |o--o{ staff_shift : "department_id"
```

## Access control

5 tables

Logins and role-based permissions. A user can hold several roles through `user_role`.

Links out to: `staff` (shown with their key only)

```mermaid
%%{init: {'theme':'base','themeVariables':{'primaryColor':'#ffffff','primaryBorderColor':'#2f5d62','primaryTextColor':'#1d2b2c','lineColor':'#2f5d62','tertiaryColor':'#eef4f3','fontFamily':'Public Sans, Helvetica, Arial, sans-serif','fontSize':'14px'},'er':{'useMaxWidth':false}}}%%
erDiagram
    app_user {
        bigint id PK
        bigint staff_id FK,UK
        varchar username UK
        text password_hash
        bool is_active
        timestamptz last_login_at "null"
        timestamptz created_at
    }
    role {
        bigint id PK
        varchar code UK
        varchar name
    }
    permission {
        bigint id PK
        varchar code UK
        text description "null"
    }
    role_permission {
        bigint role_id PK,FK
        bigint permission_id PK,FK
    }
    user_role {
        bigint user_id PK,FK
        bigint role_id PK,FK
        timestamptz assigned_at
        bigint assigned_by FK "null"
    }
    staff {
        bigint id PK
    }
    staff ||--o| app_user : "staff_id"
    role ||--o{ role_permission : "role_id"
    permission ||--o{ role_permission : "permission_id"
    app_user ||--o{ user_role : "user_id"
    role ||--o{ user_role : "role_id"
    app_user |o--o{ user_role : "assigned_by"
```

## Patients

9 tables

The patient record created at reception, reused on every return visit.

Links out to: `medication` (shown with their key only)

```mermaid
%%{init: {'theme':'base','themeVariables':{'primaryColor':'#ffffff','primaryBorderColor':'#2f5d62','primaryTextColor':'#1d2b2c','lineColor':'#2f5d62','tertiaryColor':'#eef4f3','fontFamily':'Public Sans, Helvetica, Arial, sans-serif','fontSize':'14px'},'er':{'useMaxWidth':false}}}%%
erDiagram
    patient {
        bigint id PK
        varchar patient_number UK
        varchar first_name
        varchar last_name
        text sex
        date date_of_birth "null"
        bool dob_estimated
        varchar national_id UK "null"
        text address "null"
        timestamptz created_at
        bigint created_by FK "null"
    }
    patient_contact {
        bigint id PK
        bigint patient_id FK
        text contact_type
        varchar contact
        bool is_primary
    }
    patient_emergency_contact {
        bigint id PK
        bigint patient_id FK
        varchar first_name
        varchar last_name
        varchar relationship "null"
        varchar phone
    }
    patient_allergy {
        bigint id PK
        bigint patient_id FK
        varchar allergen
        text reaction "null"
        text severity "null"
        bigint recorded_by FK "null"
        timestamptz recorded_at
    }
    patient_medical_history {
        bigint id PK
        bigint patient_id FK
        text medical_condition
        varchar category "null"
        smallint since_year "null"
        text notes "null"
        bigint recorded_by FK "null"
        timestamptz recorded_at
    }
    patient_current_medication {
        bigint id PK
        bigint patient_id FK
        bigint medication_id FK "null"
        varchar medication_name
        varchar dose "null"
        date started_on "null"
        bool is_active
        bigint recorded_by FK "null"
        timestamptz recorded_at
    }
    patient_risk_factor {
        bigint id PK
        bigint patient_id FK
        varchar category
        text description "null"
        bigint recorded_by FK "null"
        timestamptz recorded_at
    }
    insurance_provider {
        bigint id PK
        varchar name UK
        varchar phone "null"
        varchar email "null"
        text claims_notes "null"
        bool is_active
    }
    patient_insurance {
        bigint id PK
        bigint patient_id FK
        bigint provider_id FK
        varchar policy_number
        varchar member_number "null"
        varchar policy_holder_name "null"
        text relationship_to_holder "null"
        date valid_from "null"
        date valid_to "null"
        bool is_active
    }
    medication {
        bigint id PK
    }
    patient ||--o{ patient_contact : "patient_id"
    patient ||--o{ patient_emergency_contact : "patient_id"
    patient ||--o{ patient_allergy : "patient_id"
    patient ||--o{ patient_medical_history : "patient_id"
    patient ||--o{ patient_current_medication : "patient_id"
    medication |o--o{ patient_current_medication : "medication_id"
    patient ||--o{ patient_risk_factor : "patient_id"
    patient ||--o{ patient_insurance : "patient_id"
    insurance_provider ||--o{ patient_insurance : "provider_id"
```

## Visit flow & queue

4 tables

A visit moves through checkpoints. Each entry into a checkpoint is a log row, so revisits and skips are recorded. The queue is open visits grouped by current checkpoint.

Links out to: `patient`, `patient_insurance` (shown with their key only)

```mermaid
%%{init: {'theme':'base','themeVariables':{'primaryColor':'#ffffff','primaryBorderColor':'#2f5d62','primaryTextColor':'#1d2b2c','lineColor':'#2f5d62','tertiaryColor':'#eef4f3','fontFamily':'Public Sans, Helvetica, Arial, sans-serif','fontSize':'14px'},'er':{'useMaxWidth':false}}}%%
erDiagram
    checkpoint {
        bigint id PK
        varchar code UK
        varchar name
        smallint sequence UK
    }
    visitation {
        bigint id PK
        varchar visit_number UK
        bigint patient_id FK
        text visit_type
        text priority
        bool patient_consent
        bigint patient_insurance_id FK "null"
        bigint insurance_verified_by FK "null"
        timestamptz insurance_verified_at "null"
        bigint current_checkpoint_id FK "null"
        text status
        timestamptz arrived_at
        bigint registered_by FK
        timestamptz closed_at "null"
        bigint closed_by FK "null"
        text closure_reason "null"
    }
    visitation_checkpoint_log {
        bigint id PK
        bigint visitation_id FK
        bigint checkpoint_id FK
        timestamptz entered_at
        timestamptz cleared_at "null"
        bigint cleared_by FK "null"
        bool skipped
        text note "null"
    }
    visitation_vitals {
        bigint id PK
        bigint visitation_id FK
        numeric temperature_c "null"
        smallint bp_systolic "null"
        smallint bp_diastolic "null"
        smallint pulse_bpm "null"
        smallint resp_rate "null"
        smallint spo2_pct "null"
        numeric weight_kg "null"
        numeric height_cm "null"
        numeric muac_cm "null"
        text notes "null"
        bigint recorded_by FK
        timestamptz recorded_at
    }
    patient {
        bigint id PK
    }
    patient_insurance {
        bigint id PK
    }
    patient ||--o{ visitation : "patient_id"
    patient_insurance |o--o{ visitation : "patient_insurance_id"
    checkpoint |o--o{ visitation : "current_checkpoint_id"
    visitation ||--o{ visitation_checkpoint_log : "visitation_id"
    checkpoint ||--o{ visitation_checkpoint_log : "checkpoint_id"
    visitation ||--o{ visitation_vitals : "visitation_id"
```

## Clinical

8 tables

Consultations, lab orders and results, and prescriptions. Order and prescription status is what shows as pending to the lab and pharmacy.

Links out to: `app_user`, `visitation` (shown with their key only)

```mermaid
%%{init: {'theme':'base','themeVariables':{'primaryColor':'#ffffff','primaryBorderColor':'#2f5d62','primaryTextColor':'#1d2b2c','lineColor':'#2f5d62','tertiaryColor':'#eef4f3','fontFamily':'Public Sans, Helvetica, Arial, sans-serif','fontSize':'14px'},'er':{'useMaxWidth':false}}}%%
erDiagram
    consultation {
        bigint id PK
        bigint visitation_id FK
        bigint clinician_id FK
        text consultation_kind
        text chief_complaint "null"
        text history "null"
        text examination "null"
        text plan "null"
        text advice "null"
        timestamptz started_at
        timestamptz completed_at "null"
    }
    consultation_diagnosis {
        bigint id PK
        bigint consultation_id FK
        varchar icd10_code "null"
        text description
        bool is_primary
    }
    lab_test_type {
        bigint id PK
        varchar code UK
        varchar name
        varchar specimen_type "null"
        varchar unit "null"
        varchar reference_range "null"
        numeric price
        bool is_active
    }
    lab_order {
        bigint id PK
        bigint visitation_id FK
        bigint consultation_id FK
        bigint test_type_id FK
        bigint ordered_by FK
        timestamptz ordered_at
        text status
        bigint collected_by FK "null"
        timestamptz collected_at "null"
        text cancel_reason "null"
    }
    lab_result {
        bigint id PK
        bigint lab_order_id FK,UK
        text result_value
        varchar unit "null"
        text flag "null"
        text comment "null"
        bigint entered_by FK
        timestamptz entered_at
        bigint reviewed_by FK "null"
        timestamptz reviewed_at "null"
    }
    medication {
        bigint id PK
        varchar generic_name UK
        varchar brand_name "null"
        varchar dosage_form UK "null"
        varchar strength UK "null"
        numeric unit_price
        bool is_active
    }
    prescription {
        bigint id PK
        bigint visitation_id FK
        bigint consultation_id FK
        bigint prescribed_by FK
        timestamptz prescribed_at
        text status
        bigint dispensed_by FK "null"
        timestamptz dispensed_at "null"
    }
    prescription_item {
        bigint id PK
        bigint prescription_id FK
        bigint medication_id FK
        varchar dose
        text route
        varchar frequency_code
        smallint duration_days "null"
        int quantity_prescribed
        int quantity_dispensed
        text instructions "null"
    }
    app_user {
        bigint id PK
    }
    visitation {
        bigint id PK
    }
    visitation ||--o{ consultation : "visitation_id"
    app_user ||--o{ consultation : "clinician_id"
    consultation ||--o{ consultation_diagnosis : "consultation_id"
    visitation ||--o{ lab_order : "visitation_id"
    consultation ||--o{ lab_order : "consultation_id"
    lab_test_type ||--o{ lab_order : "test_type_id"
    lab_order ||--o| lab_result : "lab_order_id"
    visitation ||--o{ prescription : "visitation_id"
    consultation ||--o{ prescription : "consultation_id"
    prescription ||--o{ prescription_item : "prescription_id"
    medication ||--o{ prescription_item : "medication_id"
```

## Billing & insurance claims

8 tables

One bill per visit, with lines that trace back to the fee, lab order or prescription item. Insured portions become claims; insurer remittances are allocated across claims.

Links out to: `insurance_provider`, `lab_order`, `patient_insurance`, `prescription_item`, `visitation` (shown with their key only)

```mermaid
%%{init: {'theme':'base','themeVariables':{'primaryColor':'#ffffff','primaryBorderColor':'#2f5d62','primaryTextColor':'#1d2b2c','lineColor':'#2f5d62','tertiaryColor':'#eef4f3','fontFamily':'Public Sans, Helvetica, Arial, sans-serif','fontSize':'14px'},'er':{'useMaxWidth':false}}}%%
erDiagram
    service_fee {
        bigint id PK
        varchar code UK
        varchar name
        text category
        numeric price
        bool is_active
    }
    bill {
        bigint id PK
        varchar bill_number UK
        bigint visitation_id FK,UK
        bigint patient_insurance_id FK "null"
        text status
        numeric total_amount
        numeric insurance_portion
        numeric patient_portion
        timestamptz created_at
        timestamptz finalised_at "null"
        bigint finalised_by FK "null"
    }
    bill_item {
        bigint id PK
        bigint bill_id FK
        text item_type
        bigint service_fee_id FK "null"
        bigint lab_order_id FK,UK "null"
        bigint prescription_item_id FK,UK "null"
        varchar description
        int quantity
        numeric unit_price
        numeric line_total "null"
    }
    payment {
        bigint id PK
        bigint bill_id FK
        varchar receipt_number UK
        numeric amount
        text method
        varchar reference "null"
        bigint received_by FK
        timestamptz received_at
    }
    insurance_claim {
        bigint id PK
        varchar claim_number UK
        bigint bill_id FK
        bigint patient_insurance_id FK
        bigint provider_id FK
        numeric amount_claimed
        numeric amount_approved "null"
        numeric amount_paid
        text status
        bigint created_by FK
        timestamptz created_at
        bigint submitted_by FK "null"
        timestamptz submitted_at "null"
        timestamptz decided_at "null"
        text rejection_reason "null"
    }
    insurance_claim_event {
        bigint id PK
        bigint claim_id FK
        text status
        text note "null"
        bigint recorded_by FK
        timestamptz recorded_at
    }
    insurance_remittance {
        bigint id PK
        bigint provider_id FK,UK
        varchar reference UK
        numeric amount
        date received_on
        bigint recorded_by FK
    }
    insurance_remittance_allocation {
        bigint remittance_id PK,FK
        bigint claim_id PK,FK
        numeric amount
    }
    insurance_provider {
        bigint id PK
    }
    lab_order {
        bigint id PK
    }
    patient_insurance {
        bigint id PK
    }
    prescription_item {
        bigint id PK
    }
    visitation {
        bigint id PK
    }
    visitation ||--o| bill : "visitation_id"
    patient_insurance |o--o{ bill : "patient_insurance_id"
    bill ||--o{ bill_item : "bill_id"
    service_fee |o--o{ bill_item : "service_fee_id"
    lab_order |o--o| bill_item : "lab_order_id"
    prescription_item |o--o| bill_item : "prescription_item_id"
    bill ||--o{ payment : "bill_id"
    bill ||--o{ insurance_claim : "bill_id"
    patient_insurance ||--o{ insurance_claim : "patient_insurance_id"
    insurance_provider ||--o{ insurance_claim : "provider_id"
    insurance_claim ||--o{ insurance_claim_event : "claim_id"
    insurance_provider ||--o{ insurance_remittance : "provider_id"
    insurance_remittance ||--o{ insurance_remittance_allocation : "remittance_id"
    insurance_claim ||--o{ insurance_remittance_allocation : "claim_id"
```

## ANC extension

4 tables

Pregnancy episodes, ANC contact details (1:1 with a visit), specialist sessions and booked appointments. The general clinic tables do not depend on any of these.

Links out to: `patient`, `staff`, `visitation` (shown with their key only)

```mermaid
%%{init: {'theme':'base','themeVariables':{'primaryColor':'#ffffff','primaryBorderColor':'#2f5d62','primaryTextColor':'#1d2b2c','lineColor':'#2f5d62','tertiaryColor':'#eef4f3','fontFamily':'Public Sans, Helvetica, Arial, sans-serif','fontSize':'14px'},'er':{'useMaxWidth':false}}}%%
erDiagram
    pregnancy {
        bigint id PK
        bigint patient_id FK
        date lmp_date "null"
        date edd
        text edd_method
        smallint gravida "null"
        smallint parity "null"
        date booking_date
        text risk_level
        text status
        date outcome_date "null"
        text outcome_notes "null"
        bigint created_by FK
        timestamptz created_at
    }
    anc_visit {
        bigint id PK
        bigint visitation_id FK,UK
        bigint pregnancy_id FK,UK
        smallint contact_number UK
        numeric gestational_age_weeks "null"
        numeric fundal_height_cm "null"
        smallint fetal_heart_rate_bpm "null"
        text fetal_presentation "null"
        bool fetal_movement "null"
        bool oedema "null"
        varchar urine_protein "null"
        text notes "null"
        bigint recorded_by FK
        timestamptz recorded_at
    }
    specialist_session {
        bigint id PK
        bigint staff_id FK
        text service
        date session_date
        time start_time
        time end_time
        smallint max_appointments "null"
        text status
    }
    appointment {
        bigint id PK
        bigint patient_id FK
        bigint pregnancy_id FK "null"
        bigint specialist_session_id FK "null"
        date scheduled_date
        time scheduled_time "null"
        varchar purpose "null"
        text status
        bigint visitation_id FK,UK "null"
        bigint booked_by FK
        timestamptz created_at
    }
    patient {
        bigint id PK
    }
    staff {
        bigint id PK
    }
    visitation {
        bigint id PK
    }
    patient ||--o{ pregnancy : "patient_id"
    visitation ||--o| anc_visit : "visitation_id"
    pregnancy ||--o{ anc_visit : "pregnancy_id"
    staff ||--o{ specialist_session : "staff_id"
    patient ||--o{ appointment : "patient_id"
    pregnancy |o--o{ appointment : "pregnancy_id"
    specialist_session |o--o{ appointment : "specialist_session_id"
    visitation |o--o| appointment : "visitation_id"
```

Generated from `clinic_schema.sql`. Views: v_visit_queue, v_visit_open_items, v_anc_active, v_anc_expected_deliveries_by_month, v_revenue_daily, v_claims_outstanding.
