<?php

namespace App\Helpers\Satsets;

use App\Helpers\BridgingSatsetHelper;
use Carbon\Carbon;
use Illuminate\Support\Str;

class PostKunjunganIgdHelper
{
    public static function generateUuid()
    {
        return (string) Str::orderedUuid();
    }

    public static function form($request, $pasien_uuid)
    {
        $send = [
            'message' =>  'failed',
            'data' => null
        ];

        $organization_id = BridgingSatsetHelper::organization_id();


        $entrys= self::encounter($request, $pasien_uuid, $organization_id);

        $form = [
          "resourceType" => "Bundle",
          "type" => "transaction",
          "entry" => [

            // 1. Encounter, Condition
            $entrys['encounter'],
            // 2. CarePlan dll belm dikerjakan
            ]
        ];

        // push condition
        for ($i=0; $i < count($entrys['condition']) ; $i++) { 
            array_push($form['entry'], $entrys['condition'][$i]);
        }

        $send['message'] = 'success';
        $send['data'] = $form;

        return $send;

        
    }
    public static function encounter($request, $pasien_uuid, $organization_id)
    {
       $formEncounter =  
       [
        "fullUrl" => "urn:uuid:{{Encounter_id}}",
        "resource" => [
            "resourceType" => "Encounter",
            "identifier" => [
                [
                    "system" => "http://sys-ids.kemkes.go.id/encounter/{{Org_ID}}",
                    "value" => "KSP20240001",
                ],
            ],
            "status" => "finished",
            "class" => [
                "system" => "http://terminology.hl7.org/CodeSystem/v3-ActCode",
                "code" => "EMER",
                "display" => "emergency",
            ],
            "subject" => [
                "reference" => "Patient/{{Patient_ID}}",
                "display" => "{{Patient_Name}}",
            ],
            "participant" => [
                [
                    "type" => [
                        [
                            "coding" => [
                                [
                                    "system" => "http://terminology.hl7.org/CodeSystem/v3-ParticipationType",
                                    "code" => "ATND",
                                    "display" => "attender",
                                ],
                            ],
                        ],
                    ],
                    "individual" => [
                        "reference" => "Practitioner/{{Practitioner_ID}}",
                        "display" => "{{Practitioner_Name}}",
                    ],
                ],
            ],
            "period" => [
                "start" => "2023-07-04T08:30:00+00:00",
                "end" => "2023-07-04T14:00:00+00:00",
            ],
            "location" => [
                [
                    "location" => [
                        "reference" => "Location/{{Location_RT}}",
                        "display" => "Ruangan Triase, Instalasi Gawat Darurat, Gedung Utama, Lantai 1",
                    ],
                    "period" => [
                        "start" => "2023-07-04T08:30:00+00:00",
                        "end" => "2023-07-04T08:40:00+00:00",
                    ],
                    "extension" => [
                        [
                            "url" => "https://fhir.kemkes.go.id/r4/StructureDefinition/ServiceClass",
                            "extension" => [
                                [
                                    "url" => "value",
                                    "valueCodeableConcept" => [
                                        "coding" => [
                                            [
                                                "system" => "http://terminology.kemkes.go.id/CodeSystem/locationServiceClass-Outpatient",
                                                "code" => "reguler",
                                                "display" =>
                                                    "Kelas Reguler",
                                            ],
                                        ],
                                    ],
                                ],
                                [
                                    "url" => "upgradeClassIndicator",
                                    "valueCodeableConcept" => [
                                        "coding" => [
                                            [
                                                "system" => "http://terminology.kemkes.go.id/CodeSystem/locationUpgradeClass",
                                                "code" => "kelas-tetap",
                                                "display" =>
                                                    "Kelas Tetap Perawatan",
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    "location" => [
                        "reference" => "Location/{{Location_RTK}}",
                        "display" => "Ruangan Tindakan Kebidanan, Instalasi Gawat Darurat, Gedung Utama, Lantai 1",
                    ],
                    "period" => [
                        "start" => "2023-07-04T08:40:00+00:00",
                        "end" => "2023-07-04T14:00:00+00:00",
                    ],
                    "extension" => [
                        [
                            "url" =>
                                "https://fhir.kemkes.go.id/r4/StructureDefinition/ServiceClass",
                            "extension" => [
                                [
                                    "url" => "value",
                                    "valueCodeableConcept" => [
                                        "coding" => [
                                            [
                                                "system" =>
                                                    "http://terminology.kemkes.go.id/CodeSystem/locationServiceClass-Outpatient",
                                                "code" => "reguler",
                                                "display" =>
                                                    "Kelas Reguler",
                                            ],
                                        ],
                                    ],
                                ],
                                [
                                    "url" => "upgradeClassIndicator",
                                    "valueCodeableConcept" => [
                                        "coding" => [
                                            [
                                                "system" => "http://terminology.kemkes.go.id/CodeSystem/locationUpgradeClass",
                                                "code" => "kelas-tetap",
                                                "display" =>
                                                    "Kelas Tetap Perawatan",
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            "diagnosis" => [
                [
                    "condition" => [
                        "reference" => "urn:uuid:{{Condition_DiagnosisAwal}}",
                        "display" => "Abnormal uterine and vaginal bleeding, unspecified",
                    ],
                    "use" => [
                        "coding" => [
                            [
                                "system" => "http://terminology.hl7.org/CodeSystem/diagnosis-role",
                                "code" => "AD",
                                "display" => "Admission diagnosis ",
                            ],
                        ],
                    ],
                ],
            ],
            "statusHistory" => [
                [
                    "status" => "arrived",
                    "period" => [
                        "start" => "2023-07-04T08:30:00+00:00",
                        "end" => "2023-07-04T08:31:00+00:00",
                    ],
                ],
                [
                    "status" => "triaged",
                    "period" => [
                        "start" => "2023-07-04T08:31:00+00:00",
                        "end" => "2023-07-04T08:40:00+00:00",
                    ],
                ],
                [
                    "status" => "in-progress",
                    "period" => [
                        "start" => "2023-07-04T08:40:00+00:00",
                        "end" => "2023-07-04T14:00:00+00:00",
                    ],
                ],
                [
                    "status" => "finished",
                    "period" => [
                        "start" => "2023-07-04T14:00:00+00:00",
                        "end" => "2023-07-04T14:00:00+00:00",
                    ],
                ],
            ],
            "hospitalization" => [
                "dischargeDisposition" => [
                    "coding" => [
                        [
                            "system" => "http://terminology.hl7.org/CodeSystem/discharge-disposition",
                            "code" => "oth",
                            "display" => "Other",
                        ],
                    ],
                    "text" => "Pasien dipindahkan dari IGD ke rawat inap.",
                ],
            ],
            "serviceProvider" => ["reference" => "Organization/{{Org_ID}}"],
        ],
        "request" => ["method" => "POST", "url" => "Encounter"],
      ];


      $entrys['encounter'] = $formEncounter;
      

      return $entrys;
    }

   



    public function ygHarusDikerjakan()
    {
      $arrayVar = [
        "resourceType" => "Bundle",
        "type" => "transaction",
        "entry" => [

          // `1. Encounter dikerjakan
            [
                "fullUrl" => "urn:uuid:{{Encounter_id}}",
                "resource" => [
                    "resourceType" => "Encounter",
                    "identifier" => [
                        [
                            "system" =>
                                "http://sys-ids.kemkes.go.id/encounter/{{Org_ID}}",
                            "value" => "KSP20240001",
                        ],
                    ],
                    "status" => "finished",
                    "class" => [
                        "system" =>
                            "http://terminology.hl7.org/CodeSystem/v3-ActCode",
                        "code" => "EMER",
                        "display" => "emergency",
                    ],
                    "subject" => [
                        "reference" => "Patient/{{Patient_ID}}",
                        "display" => "{{Patient_Name}}",
                    ],
                    "participant" => [
                        [
                            "type" => [
                                [
                                    "coding" => [
                                        [
                                            "system" =>
                                                "http://terminology.hl7.org/CodeSystem/v3-ParticipationType",
                                            "code" => "ATND",
                                            "display" => "attender",
                                        ],
                                    ],
                                ],
                            ],
                            "individual" => [
                                "reference" => "Practitioner/{{Practitioner_ID}}",
                                "display" => "{{Practitioner_Name}}",
                            ],
                        ],
                    ],
                    "period" => [
                        "start" => "2023-07-04T08:30:00+00:00",
                        "end" => "2023-07-04T14:00:00+00:00",
                    ],
                    "location" => [
                        [
                            "location" => [
                                "reference" => "Location/{{Location_RT}}",
                                "display" =>
                                    "Ruangan Triase, Instalasi Gawat Darurat, Gedung Utama, Lantai 1",
                            ],
                            "period" => [
                                "start" => "2023-07-04T08:30:00+00:00",
                                "end" => "2023-07-04T08:40:00+00:00",
                            ],
                            "extension" => [
                                [
                                    "url" =>
                                        "https://fhir.kemkes.go.id/r4/StructureDefinition/ServiceClass",
                                    "extension" => [
                                        [
                                            "url" => "value",
                                            "valueCodeableConcept" => [
                                                "coding" => [
                                                    [
                                                        "system" =>
                                                            "http://terminology.kemkes.go.id/CodeSystem/locationServiceClass-Outpatient",
                                                        "code" => "reguler",
                                                        "display" =>
                                                            "Kelas Reguler",
                                                    ],
                                                ],
                                            ],
                                        ],
                                        [
                                            "url" => "upgradeClassIndicator",
                                            "valueCodeableConcept" => [
                                                "coding" => [
                                                    [
                                                        "system" =>
                                                            "http://terminology.kemkes.go.id/CodeSystem/locationUpgradeClass",
                                                        "code" => "kelas-tetap",
                                                        "display" =>
                                                            "Kelas Tetap Perawatan",
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            "location" => [
                                "reference" => "Location/{{Location_RTK}}",
                                "display" =>
                                    "Ruangan Tindakan Kebidanan, Instalasi Gawat Darurat, Gedung Utama, Lantai 1",
                            ],
                            "period" => [
                                "start" => "2023-07-04T08:40:00+00:00",
                                "end" => "2023-07-04T14:00:00+00:00",
                            ],
                            "extension" => [
                                [
                                    "url" =>
                                        "https://fhir.kemkes.go.id/r4/StructureDefinition/ServiceClass",
                                    "extension" => [
                                        [
                                            "url" => "value",
                                            "valueCodeableConcept" => [
                                                "coding" => [
                                                    [
                                                        "system" =>
                                                            "http://terminology.kemkes.go.id/CodeSystem/locationServiceClass-Outpatient",
                                                        "code" => "reguler",
                                                        "display" =>
                                                            "Kelas Reguler",
                                                    ],
                                                ],
                                            ],
                                        ],
                                        [
                                            "url" => "upgradeClassIndicator",
                                            "valueCodeableConcept" => [
                                                "coding" => [
                                                    [
                                                        "system" =>
                                                            "http://terminology.kemkes.go.id/CodeSystem/locationUpgradeClass",
                                                        "code" => "kelas-tetap",
                                                        "display" =>
                                                            "Kelas Tetap Perawatan",
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    "diagnosis" => [
                        [
                            "condition" => [
                                "reference" =>
                                    "urn:uuid:{{Condition_DiagnosisAwal}}",
                                "display" =>
                                    "Abnormal uterine and vaginal bleeding, unspecified",
                            ],
                            "use" => [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://terminology.hl7.org/CodeSystem/diagnosis-role",
                                        "code" => "AD",
                                        "display" => "Admission diagnosis ",
                                    ],
                                ],
                            ],
                        ],
                    ],
                    "statusHistory" => [
                        [
                            "status" => "arrived",
                            "period" => [
                                "start" => "2023-07-04T08:30:00+00:00",
                                "end" => "2023-07-04T08:31:00+00:00",
                            ],
                        ],
                        [
                            "status" => "triaged",
                            "period" => [
                                "start" => "2023-07-04T08:31:00+00:00",
                                "end" => "2023-07-04T08:40:00+00:00",
                            ],
                        ],
                        [
                            "status" => "in-progress",
                            "period" => [
                                "start" => "2023-07-04T08:40:00+00:00",
                                "end" => "2023-07-04T14:00:00+00:00",
                            ],
                        ],
                        [
                            "status" => "finished",
                            "period" => [
                                "start" => "2023-07-04T14:00:00+00:00",
                                "end" => "2023-07-04T14:00:00+00:00",
                            ],
                        ],
                    ],
                    "hospitalization" => [
                        "dischargeDisposition" => [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.hl7.org/CodeSystem/discharge-disposition",
                                    "code" => "oth",
                                    "display" => "Other",
                                ],
                            ],
                            "text" => "Pasien dipindahkan dari IGD ke rawat inap.",
                        ],
                    ],
                    "serviceProvider" => ["reference" => "Organization/{{Org_ID}}"],
                ],
                "request" => ["method" => "POST", "url" => "Encounter"],
            ],

        // 2. Condition DiagnosisAwal
            [
                "fullUrl" => "urn:uuid:{{Condition_DiagnosisAwal}}",
                "resource" => [
                    "resourceType" => "Condition",
                    "clinicalStatus" => [
                        "coding" => [
                            [
                                "system" =>
                                    "http://terminology.hl7.org/CodeSystem/condition-clinical",
                                "code" => "active",
                                "display" => "Active",
                            ],
                        ],
                    ],
                    "category" => [
                        [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.hl7.org/CodeSystem/condition-category",
                                    "code" => "encounter-diagnosis",
                                    "display" => "Encounter Diagnosis",
                                ],
                            ],
                        ],
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://hl7.org/fhir/sid/icd-10",
                                "code" => "N93.9",
                                "display" =>
                                    "Abnormal uterine and vaginal bleeding, unspecified",
                            ],
                        ],
                    ],
                    "subject" => [
                        "reference" => "Patient/{{Patient_ID}}",
                        "display" => "{{Patient_Name}}",
                    ],
                    "encounter" => ["reference" => "urn:uuid:{{Encounter_id}}"],
                    "onsetDateTime" => "2023-07-04T09:40:00+00:00",
                    "recordedDate" => "2023-07-04T09:40:00+00:00",
                    "note" => [
                        [
                            "text" =>
                                "Pasien {{Patient_Name}} mengalami abnormal uterus dan perdarahan pervagina",
                        ],
                    ],
                ],
                "request" => ["method" => "POST", "url" => "Condition"],
            ],

        // 3. Condition DiagnosisKerja
            [
                "fullUrl" => "urn:uuid:{{Condition_DiagnosisKerja}}",
                "resource" => [
                    "resourceType" => "Condition",
                    "clinicalStatus" => [
                        "coding" => [
                            [
                                "system" =>
                                    "http://terminology.hl7.org/CodeSystem/condition-clinical",
                                "code" => "active",
                                "display" => "Active",
                            ],
                        ],
                    ],
                    "category" => [
                        [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.hl7.org/CodeSystem/condition-category",
                                    "code" => "encounter-diagnosis",
                                    "display" => "Encounter Diagnosis",
                                ],
                            ],
                        ],
                    ],
                    "verificationStatus" => [
                        "coding" => [
                            [
                                "system" =>
                                    "http://terminology.hl7.org/CodeSystem/condition-ver-status",
                                "code" => "provisional",
                                "display" => "Provisional",
                            ],
                        ],
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://hl7.org/fhir/sid/icd-10",
                                "code" => "O71.0",
                                "display" =>
                                    "Rupture of uterus before onset of labour",
                            ],
                        ],
                    ],
                    "subject" => [
                        "reference" => "Patient/{{Patient_ID}}",
                        "display" => "{{Patient_Name}}",
                    ],
                    "encounter" => ["reference" => "urn:uuid:{{Encounter_id}}"],
                    "onsetDateTime" => "2023-07-04T09:40:00+00:00",
                    "recordedDate" => "2023-07-04T09:40:00+00:00",
                    "note" => [
                        [
                            "text" =>
                                "Diagnosis kerja dari Pasien {{Patient_Name}} berupa ruptur uteri sebelum proses persalinan dimulai",
                        ],
                    ],
                ],
                "request" => ["method" => "POST", "url" => "Condition"],
            ],

        // 4. Condition DoiagnosisiBanding
            [
                "fullUrl" => "urn:uuid:{{Condition_DiagnosisBanding}}",
                "resource" => [
                    "resourceType" => "Condition",
                    "clinicalStatus" => [
                        "coding" => [
                            [
                                "system" =>
                                    "http://terminology.hl7.org/CodeSystem/condition-clinical",
                                "code" => "active",
                                "display" => "Active",
                            ],
                        ],
                    ],
                    "category" => [
                        [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.hl7.org/CodeSystem/condition-category",
                                    "code" => "encounter-diagnosis",
                                    "display" => "Encounter Diagnosis",
                                ],
                            ],
                        ],
                    ],
                    "verificationStatus" => [
                        "coding" => [
                            [
                                "system" =>
                                    "http://terminology.hl7.org/CodeSystem/condition-ver-status",
                                "code" => "differential",
                                "display" => "Differential",
                            ],
                        ],
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://hl7.org/fhir/sid/icd-10",
                                "code" => "O03.9",
                                "display" =>
                                    "Spontaneous abortion: complete or unspecified, without complication",
                            ],
                        ],
                    ],
                    "subject" => [
                        "reference" => "Patient/{{Patient_ID}}",
                        "display" => "{{Patient_Name}}",
                    ],
                    "encounter" => ["reference" => "urn:uuid:{{Encounter_id}}"],
                    "onsetDateTime" => "2023-07-04T09:40:00+00:00",
                    "recordedDate" => "2023-07-04T09:40:00+00:00",
                    "note" => [
                        [
                            "text" =>
                                "Diagnosis banding dari Pasien {{Patient_Name}} berupa aborsi spontan tanpa komplikasi",
                        ],
                    ],
                ],
                "request" => ["method" => "POST", "url" => "Condition"],
            ],

        // 5. Procedure Emergency
            [
                "fullUrl" => "urn:uuid:{{Procedure_Emergency}}",
                "resource" => [
                    "resourceType" => "Procedure",
                    "status" => "completed",
                    "category" => [
                        "coding" => [
                            [
                                "system" => "http://snomed.info/sct",
                                "code" => "373110003",
                                "display" => "Emergency procedure",
                            ],
                        ],
                        "text" => "Prosedur emergensi",
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://hl7.org/fhir/sid/icd-9-cm",
                                "code" => "74.0",
                                "display" => "Classical cesarean section",
                            ],
                        ],
                    ],
                    "subject" => [
                        "reference" => "Patient/{{Patient_ID}}",
                        "display" => "{{Patient_Name}}",
                    ],
                    "encounter" => ["reference" => "urn:uuid:{{Encounter_id}}"],
                    "performedPeriod" => [
                        "start" => "2023-07-04T09:45:00+00:00",
                        "end" => "2023-07-04T13:00:00+00:00",
                    ],
                    "performer" => [
                        [
                            "actor" => [
                                "reference" => "Practitioner/{{Practitioner_ID}}",
                                "display" => "{{Practitioner_Name}}",
                            ],
                        ],
                    ],
                    "reasonCode" => [
                        [
                            "coding" => [
                                [
                                    "system" => "http://hl7.org/fhir/sid/icd-10",
                                    "code" => "O71.0",
                                    "display" =>
                                        "Rupture of uterus before onset of labour",
                                ],
                            ],
                        ],
                    ],
                    "bodySite" => [
                        [
                            "coding" => [
                                [
                                    "system" => "http://snomed.info/sct",
                                    "code" => "35039007",
                                    "display" => "Uterine structure",
                                ],
                            ],
                        ],
                    ],
                    "note" => [
                        [
                            "text" =>
                                "Emergensi sectio caesarian telah dilakukan kepada Pasien {{Patient_Name}}",
                        ],
                    ],
                ],
                "request" => ["method" => "POST", "url" => "Procedure"],
            ],
            [
                "fullUrl" => "urn:uuid:{{Observation_Kesadaran}}",
                "resource" => [
                    "resourceType" => "Observation",
                    "status" => "final",
                    "identifier" => [
                        [
                            "system" =>
                                "http://sys-ids.kemkes.go.id/observation/{{Org_ID}}",
                            "value" => "A20240199",
                        ],
                    ],
                    "category" => [
                        [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.hl7.org/CodeSystem/observation-category",
                                    "code" => "exam",
                                    "display" => "Exam",
                                ],
                            ],
                        ],
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://loinc.org",
                                "code" => "67775-7",
                                "display" => "Level of responsiveness",
                            ],
                        ],
                    ],
                    "subject" => [
                        "reference" => "Patient/{{Patient_ID}}",
                        "display" => "{{Patient_Name}}",
                    ],
                    "encounter" => ["reference" => "urn:uuid:{{Encounter_id}}"],
                    "effectiveDateTime" => "2023-07-04T08:45:00+00:00",
                    "issued" => "2023-07-04T08:45:00+00:00",
                    "performer" => [
                        ["reference" => "Practitioner/{{Practitioner_ID}}"],
                    ],
                    "valueCodeableConcept" => [
                        "coding" => [
                            [
                                "system" => "http://snomed.info/sct",
                                "code" => "450847001",
                                "display" => "Response to pain",
                            ],
                        ],
                    ],
                ],
                "request" => ["method" => "POST", "url" => "Observation"],
            ],
            [
                "fullUrl" => "urn:uuid:{{Observation_Nadi}}",
                "resource" => [
                    "resourceType" => "Observation",
                    "identifier" => [
                        [
                            "system" =>
                                "http://sys-ids.kemkes.go.id/observation/{{Org_ID}}",
                            "value" => "A202401199",
                        ],
                    ],
                    "status" => "final",
                    "category" => [
                        [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.hl7.org/CodeSystem/observation-category",
                                    "code" => "vital-signs",
                                    "display" => "Vital Signs",
                                ],
                            ],
                        ],
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://loinc.org",
                                "code" => "8867-4",
                                "display" => "Heart rate",
                            ],
                        ],
                    ],
                    "subject" => [
                        "reference" => "Patient/{{Patient_ID}}",
                        "display" => "{{Patient_Name}}",
                    ],
                    "encounter" => ["reference" => "urn:uuid:{{Encounter_id}}"],
                    "effectiveDateTime" => "2023-07-04T08:45:00+00:00",
                    "issued" => "2023-07-04T08:45:00+00:00",
                    "performer" => [
                        ["reference" => "Practitioner/{{Practitioner_ID}}"],
                    ],
                    "valueQuantity" => [
                        "value" => 80,
                        "unit" => "beats/minute",
                        "system" => "http://unitsofmeasure.org",
                        "code" => "/min",
                    ],
                ],
                "request" => ["method" => "POST", "url" => "Observation"],
            ],
            [
                "fullUrl" => "urn:uuid:{{Observation_RisikoJatuh}}",
                "resource" => [
                    "resourceType" => "Observation",
                    "status" => "final",
                    "identifier" => [
                        [
                            "system" =>
                                "http://sys-ids.kemkes.go.id/observation/{{Org_ID}}",
                            "value" => "A2024011199",
                        ],
                    ],
                    "category" => [
                        [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.hl7.org/CodeSystem/observation-category",
                                    "code" => "exam",
                                    "display" => "Exam",
                                ],
                            ],
                        ],
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://loinc.org",
                                "code" => "59461-4",
                                "display" => "Fall risk level [Morse Fall Scale]",
                            ],
                        ],
                    ],
                    "subject" => [
                        "reference" => "Patient/{{Patient_ID}}",
                        "display" => "{{Patient_Name}}",
                    ],
                    "encounter" => ["reference" => "urn:uuid:{{Encounter_id}}"],
                    "effectiveDateTime" => "2023-07-04T08:45:00+00:00",
                    "issued" => "2023-07-04T08:45:00+00:00",
                    "performer" => [
                        ["reference" => "Practitioner/{{Practitioner_ID}}"],
                    ],
                    "valueQuantity" => [
                        "value" => 30,
                        "unit" => "{score}",
                        "system" => "http://unitsofmeasure.org",
                        "code" => "{score}",
                    ],
                    "interpretation" => [
                        [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
                                    "code" => "OI000027",
                                    "display" => "25 - 44 (Risiko sedang)",
                                ],
                            ],
                            "text" => "Risiko sedang",
                        ],
                    ],
                ],
                "request" => ["method" => "POST", "url" => "Observation"],
            ],
            [
                "fullUrl" => "urn:uuid:{{CarePlan_RencanaRawat}}",
                "resource" => [
                    "resourceType" => "CarePlan",
                    "title" => "Rencana Rawat",
                    "status" => "active",
                    "category" => [
                        [
                            "coding" => [
                                [
                                    "system" => "http://snomed.info/sct",
                                    "code" => "702779007",
                                    "display" =>
                                        "Emergency health care plan agreed",
                                ],
                            ],
                        ],
                    ],
                    "intent" => "plan",
                    "description" =>
                        "Rencana rawat tanggal 4 Juli 2023: pemeriksaan laboratorium darah lengkap, USG kehamilan, tindakan caesar emergensi, injeksi oksitosin 10 IU/mL segera pasca tindakan caesar, dan perawatan lanjutan ke rawat inap dengan waktu rawat 5-7 hari",
                    "subject" => [
                        "reference" => "Patient/{{Patient_ID}}",
                        "display" => "{{Patient_Name}}",
                    ],
                    "encounter" => ["reference" => "urn:uuid:{{Encounter_id}}"],
                    "created" => "2023-07-04T09:00:00+00:00",
                    "author" => ["reference" => "Practitioner/{{Practitioner_ID}}"],
                ],
                "request" => ["method" => "POST", "url" => "CarePlan"],
            ],
            [
                "fullUrl" => "urn:uuid:{{CarePlan_Instruksi}}",
                "resource" => [
                    "resourceType" => "CarePlan",
                    "title" => "Instruksi Medik dan Keperawatan",
                    "status" => "active",
                    "intent" => "plan",
                    "category" => [
                        [
                            "coding" => [
                                [
                                    "system" => "http://snomed.info/sct",
                                    "code" => "702779007",
                                    "display" =>
                                        "Emergency health care plan agreed",
                                ],
                            ],
                        ],
                    ],
                    "description" =>
                        "Intruksi medik dan keperawatan tanggal 4 Juli 2023 berupa: operasi caesar emergensi dilakukan baik dengan atau tanpa laparotomi eksplorasi, anastesi endotrakeal, pemberian injeksi intramuscular oksitosin 10 IU/mL segera pasca caesar, darah harus dipesan dan dibawa ke Ruangan Tindakan Kebidanan, IGD",
                    "subject" => [
                        "reference" => "Patient/{{Patient_ID}}",
                        "display" => "{{Patient_Name}}",
                    ],
                    "encounter" => ["reference" => "urn:uuid:{{Encounter_id}}"],
                    "created" => "2023-07-04T09:00:00+00:00",
                    "author" => ["reference" => "Practitioner/{{Practitioner_ID}}"],
                ],
                "request" => ["method" => "POST", "url" => "CarePlan"],
            ],
            [
                "fullUrl" => "urn:uuid:{{Procedure_PraLab}}",
                "resource" => [
                    "resourceType" => "Procedure",
                    "status" => "not-done",
                    "category" => [
                        "coding" => [
                            [
                                "system" => "http://snomed.info/sct",
                                "code" => "103693007",
                                "display" => "Diagnostic procedure",
                            ],
                        ],
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://snomed.info/sct",
                                "code" => "792805006",
                                "display" => "Fasting",
                            ],
                        ],
                    ],
                    "subject" => [
                        "reference" => "Patient/{{Patient_ID}}",
                        "display" => "{{Patient_Name}}",
                    ],
                    "encounter" => ["reference" => "urn:uuid:{{Encounter_id}}"],
                    "performedPeriod" => [
                        "start" => "2023-07-04T09:13:00+00:00",
                        "end" => "2023-07-04T09:13:00+00:00",
                    ],
                    "performer" => [
                        [
                            "actor" => [
                                "reference" => "Practitioner/{{Practitioner_ID}}",
                                "display" => "{{Practitioner_Name}}",
                            ],
                        ],
                    ],
                    "reasonCode" => [
                        [
                            "coding" => [
                                [
                                    "system" => "http://hl7.org/fhir/sid/icd-10",
                                    "code" => "N93.9",
                                    "display" =>
                                        "Abnormal uterine and vaginal bleeding, unspecified",
                                ],
                            ],
                        ],
                    ],
                ],
                "request" => ["method" => "POST", "url" => "Procedure"],
            ],
            [
                "fullUrl" => "urn:uuid:{{ServiceRequest_Lab}}",
                "resource" => [
                    "resourceType" => "ServiceRequest",
                    "identifier" => [
                        [
                            "system" =>
                                "http://sys-ids.kemkes.go.id/servicerequest/{{Org_ID}}",
                            "value" => "DK19231961",
                        ],
                    ],
                    "status" => "active",
                    "intent" => "original-order",
                    "priority" => "stat",
                    "category" => [
                        [
                            "coding" => [
                                [
                                    "system" => "http://snomed.info/sct",
                                    "code" => "108252007",
                                    "display" => "Laboratory procedure",
                                ],
                            ],
                        ],
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://loinc.org",
                                "code" => "58410-2",
                                "display" => "CBC panel - Blood by Automated count",
                            ],
                        ],
                        "text" => "Pemeriksaan laboratorium darah lengkap",
                    ],
                    "subject" => [
                        "reference" => "Patient/{{Patient_ID}}",
                        "display" => "{{Patient_Name}}",
                    ],
                    "encounter" => [
                        "reference" => "urn:uuid:{{Encounter_id}}",
                        "display" =>
                            "Permintaan pemeriksaan darah lengkap untuk Ny. {{Patient_Name}} tanggal 4 Juli 2023",
                    ],
                    "occurrenceDateTime" => "2023-07-04T09:15:00+00:00",
                    "authoredOn" => "2023-07-04T09:15:00+00:00",
                    "requester" => [
                        "reference" => "Practitioner/{{Practitioner_ID}}",
                        "display" => "{{Practitioner_Name}}",
                    ],
                    "performer" => [
                        [
                            "reference" => "Practitioner/N10000005",
                            "display" => "Fatma",
                        ],
                    ],
                    "reasonCode" => [
                        [
                            "text" =>
                                "Periksa darah lengkap dengan keluhan utama pasien yaitu perdarahan pervagina",
                        ],
                    ],
                    "reasonReference" => [
                        ["reference" => "urn:uuid:{{Condition_DiagnosisAwal}}"],
                    ],
                    "supportingInfo" => [
                        ["reference" => "urn:uuid:{{Procedure_PraLab}}"],
                    ],
                    "note" => [
                        [
                            "text" =>
                                "Tidak ada persiapan khusus sebelum pemeriksaan darah lengkap",
                        ],
                    ],
                ],
                "request" => ["method" => "POST", "url" => "ServiceRequest"],
            ],
            [
                "fullUrl" => "urn:uuid:{{Specimen_Lab}}",
                "resource" => [
                    "resourceType" => "Specimen",
                    "identifier" => [
                        [
                            "system" =>
                                "http://sys-ids.kemkes.go.id/specimen/{{Org_ID}}",
                            "value" => "DK19231961",
                            "assigner" => [
                                "reference" => "Organization/{{Org_ID}}",
                            ],
                        ],
                    ],
                    "status" => "available",
                    "type" => [
                        "coding" => [
                            [
                                "system" => "http://snomed.info/sct",
                                "code" => "119297000",
                                "display" => "Blood specimen",
                            ],
                        ],
                    ],
                    "collection" => [
                        "method" => [
                            "coding" => [
                                [
                                    "system" => "http://snomed.info/sct",
                                    "code" => "82078001",
                                    "display" =>
                                        "Collection of blood specimen for laboratory",
                                ],
                            ],
                        ],
                        "collectedDateTime" => "2023-07-04T09:18:00+00:00",
                        "quantity" => ["value" => 6, "unit" => "mL"],
                        "collector" => [
                            "reference" => "Practitioner/{{Practitioner_ID}}",
                            "display" => "{{Practitioner_Name}}",
                        ],
                        "fastingStatusCodeableConcept" => [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.hl7.org/CodeSystem/v2-0916",
                                    "code" => "NF",
                                    "display" =>
                                        "The patient indicated they did not fast prior to the procedure.",
                                ],
                            ],
                        ],
                    ],
                    "processing" => [
                        ["timeDateTime" => "2023-07-04T09:25:00+00:00"],
                    ],
                    "subject" => [
                        "reference" => "Patient/{{Patient_ID}}",
                        "display" => "{{Patient_Name}}",
                    ],
                    "request" => [
                        ["reference" => "urn:uuid:{{ServiceRequest_Lab}}"],
                    ],
                    "receivedTime" => "2023-07-04T09:23:00+00:00",
                    "extension" => [
                        [
                            "url" =>
                                "https://fhir.kemkes.go.id/r4/StructureDefinition/TransportedTime",
                            "valueDateTime" => "2023-07-04T09:20:00+00:00",
                        ],
                        [
                            "url" =>
                                "https://fhir.kemkes.go.id/r4/StructureDefinition/TransportedPerson",
                            "valueContactDetail" => [
                                "name" => "Burhan",
                                "telecom" => [
                                    [
                                        "system" => "phone",
                                        "value" => "+625375162867",
                                    ],
                                ],
                            ],
                        ],
                        [
                            "url" =>
                                "https://fhir.kemkes.go.id/r4/StructureDefinition/ReceivedPerson",
                            "valueReference" => [
                                "reference" => "Practitioner/10006926841",
                                "display" => "Dr. John Doe",
                            ],
                        ],
                    ],
                ],
                "request" => ["method" => "POST", "url" => "Specimen"],
            ],
            [
                "fullUrl" => "urn:uuid:{{Observation_Lab1}}",
                "resource" => [
                    "resourceType" => "Observation",
                    "identifier" => [
                        [
                            "system" =>
                                "http://sys-ids.kemkes.go.id/observation/{{Org_ID}}",
                            "value" => "DK2024019917-1",
                        ],
                    ],
                    "status" => "final",
                    "category" => [
                        [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.hl7.org/CodeSystem/observation-category",
                                    "code" => "laboratory",
                                    "display" => "Laboratory",
                                ],
                            ],
                        ],
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://loinc.org",
                                "code" => "718-7",
                                "display" => "Hemoglobin [Mass/volume] in Blood",
                            ],
                        ],
                    ],
                    "subject" => [
                        "reference" => "Patient/{{Patient_ID}}",
                        "display" => "{{Patient_Name}}",
                    ],
                    "encounter" => ["reference" => "urn:uuid:{{Encounter_id}}"],
                    "effectiveDateTime" => "2023-07-04T09:30:00+00:00",
                    "issued" => "2023-07-04T09:30:00+00:00",
                    "performer" => [
                        ["reference" => "Practitioner/{{Practitioner_ID}}"],
                        ["reference" => "Organization/{{Org_ID}}"],
                    ],
                    "specimen" => ["reference" => "urn:uuid:{{Specimen_Lab}}"],
                    "basedOn" => [
                        ["reference" => "urn:uuid:{{ServiceRequest_Lab}}"],
                    ],
                    "valueQuantity" => [
                        "value" => 60,
                        "unit" => "fL",
                        "system" => "http://unitsofmeasure.org",
                        "code" => "fL",
                    ],
                    "interpretation" => [
                        [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.hl7.org/CodeSystem/v3-ObservationInterpretation",
                                    "code" => "L",
                                    "display" => "Low",
                                ],
                            ],
                        ],
                    ],
                    "referenceRange" => [
                        [
                            "high" => [
                                "value" => 107,
                                "unit" => "g/dL",
                                "system" => "http://unitsofmeasure.org",
                                "code" => "g/dL",
                            ],
                            "low" => [
                                "value" => 63.9,
                                "unit" => "g/dL",
                                "system" => "http://unitsofmeasure.org",
                                "code" => "g/dL",
                            ],
                        ],
                    ],
                ],
                "request" => ["method" => "POST", "url" => "Observation"],
            ],
            [
                "fullUrl" => "urn:uuid:{{Observation_Lab2}}",
                "resource" => [
                    "resourceType" => "Observation",
                    "identifier" => [
                        [
                            "system" =>
                                "http://sys-ids.kemkes.go.id/observation/{{Org_ID}}",
                            "value" => "DK024019917-2",
                        ],
                    ],
                    "status" => "final",
                    "category" => [
                        [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.hl7.org/CodeSystem/observation-category",
                                    "code" => "laboratory",
                                    "display" => "Laboratory",
                                ],
                            ],
                        ],
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://loinc.org",
                                "code" => "787-2",
                                "display" =>
                                    "MCV [Entitic volume] by Automated count",
                            ],
                        ],
                    ],
                    "subject" => [
                        "reference" => "Patient/{{Patient_ID}}",
                        "display" => "{{Patient_Name}}",
                    ],
                    "encounter" => ["reference" => "urn:uuid:{{Encounter_id}}"],
                    "effectiveDateTime" => "2023-07-04T09:30:00+00:00",
                    "issued" => "2023-07-04T09:30:00+00:00",
                    "performer" => [
                        ["reference" => "Practitioner/{{Practitioner_ID}}"],
                        ["reference" => "Organization/{{Org_ID}}"],
                    ],
                    "specimen" => ["reference" => "urn:uuid:{{Specimen_Lab}}"],
                    "basedOn" => [
                        ["reference" => "urn:uuid:{{ServiceRequest_Lab}}"],
                    ],
                    "valueQuantity" => [
                        "value" => 60,
                        "unit" => "fL",
                        "system" => "http://unitsofmeasure.org",
                        "code" => "fL",
                    ],
                    "interpretation" => [
                        [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.hl7.org/CodeSystem/v3-ObservationInterpretation",
                                    "code" => "L",
                                    "display" => "Low",
                                ],
                            ],
                        ],
                    ],
                    "referenceRange" => [
                        [
                            "high" => [
                                "value" => 107,
                                "unit" => "g/dL",
                                "system" => "http://unitsofmeasure.org",
                                "code" => "g/dL",
                            ],
                            "low" => [
                                "value" => 63.9,
                                "unit" => "g/dL",
                                "system" => "http://unitsofmeasure.org",
                                "code" => "g/dL",
                            ],
                        ],
                    ],
                ],
                "request" => ["method" => "POST", "url" => "Observation"],
            ],
            [
                "fullUrl" => "urn:uuid:{{DiagnosticReport_Lab}}",
                "resource" => [
                    "resourceType" => "DiagnosticReport",
                    "identifier" => [
                        [
                            "system" =>
                                "http://sys-ids.kemkes.go.id/diagnostic/{{Org_ID}}/lab",
                            "use" => "official",
                            "value" => "DK20240019917",
                        ],
                    ],
                    "status" => "final",
                    "category" => [
                        [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.hl7.org/CodeSystem/v2-0074",
                                    "code" => "HM",
                                    "display" => "Hematology",
                                ],
                            ],
                        ],
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://loinc.org",
                                "code" => "58410-2",
                                "display" => "CBC panel - Blood by Automated count",
                            ],
                        ],
                    ],
                    "subject" => [
                        "reference" => "Patient/{{Patient_ID}}",
                        "display" => "{{Patient_Name}}",
                    ],
                    "encounter" => ["reference" => "urn:uuid:{{Encounter_id}}"],
                    "effectiveDateTime" => "2023-07-04T09:30:00+00:00",
                    "issued" => "2023-07-04T09:30:00+00:00",
                    "performer" => [
                        ["reference" => "Practitioner/{{Practitioner_ID}}"],
                        ["reference" => "Organization/{{Org_ID}}"],
                    ],
                    "result" => [
                        [
                            "id" => "1",
                            "reference" => "urn:uuid:{{Observation_Lab1}}",
                        ],
                        [
                            "id" => "2",
                            "reference" => "urn:uuid:{{Observation_Lab2}}",
                        ],
                    ],
                    "specimen" => [["reference" => "urn:uuid:{{Specimen_Lab}}"]],
                    "basedOn" => [
                        ["reference" => "urn:uuid:{{ServiceRequest_Lab}}"],
                    ],
                    "conclusion" =>
                        "Dari pemeriksaan darah lengkap, nilai Hb dan MCV dibawah batas nilai normal",
                ],
                "request" => ["method" => "POST", "url" => "DiagnosticReport"],
            ],
            [
                "fullUrl" => "urn:uuid:{{Observation_PraRad}}",
                "resource" => [
                    "resourceType" => "Observation",
                    "status" => "final",
                    "category" => [
                        [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.hl7.org/CodeSystem/observation-category",
                                    "code" => "survey",
                                    "display" => "Survey",
                                ],
                            ],
                        ],
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://loinc.org",
                                "code" => "82810-3",
                                "display" => "Pregnancy status",
                            ],
                        ],
                    ],
                    "subject" => ["reference" => "Patient/{{Patient_ID}}"],
                    "encounter" => ["reference" => "urn:uuid:{{Encounter_id}}"],
                    "effectiveDateTime" => "2023-07-04T09:28:00+00:00",
                    "issued" => "2023-07-04T09:28:00+00:00",
                    "performer" => [
                        ["reference" => "Practitioner/{{Practitioner_ID}}"],
                    ],
                    "valueCodeableConcept" => [
                        "coding" => [
                            [
                                "system" => "http://snomed.info/sct",
                                "code" => "77386006",
                                "display" => "Pregnancy",
                            ],
                        ],
                    ],
                ],
                "request" => ["method" => "POST", "url" => "Observation"],
            ],
            [
                "fullUrl" => "urn:uuid:{{Procedure_PraRad}}",
                "resource" => [
                    "resourceType" => "Procedure",
                    "status" => "not-done",
                    "category" => [
                        "coding" => [
                            [
                                "system" => "http://snomed.info/sct",
                                "code" => "103693007",
                                "display" => "Diagnostic procedure",
                            ],
                        ],
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://snomed.info/sct",
                                "code" => "792805006",
                                "display" => "Fasting",
                            ],
                        ],
                    ],
                    "subject" => [
                        "reference" => "Patient/{{Patient_ID}}",
                        "display" => "{{Patient_Name}}",
                    ],
                    "encounter" => ["reference" => "urn:uuid:{{Encounter_id}}"],
                    "performedPeriod" => [
                        "start" => "2023-07-04T09:28:00+00:00",
                        "end" => "2023-07-04T09:28:00+00:00",
                    ],
                    "performer" => [
                        [
                            "actor" => [
                                "reference" => "Practitioner/{{Practitioner_ID}}",
                                "display" => "{{Practitioner_Name}}",
                            ],
                        ],
                    ],
                    "reasonCode" => [
                        [
                            "coding" => [
                                [
                                    "system" => "http://hl7.org/fhir/sid/icd-10",
                                    "code" => "N93.9",
                                    "display" =>
                                        "Abnormal uterine and vaginal bleeding, unspecified",
                                ],
                            ],
                        ],
                    ],
                ],
                "request" => ["method" => "POST", "url" => "Procedure"],
            ],
            [
                "fullUrl" => "urn:uuid:{{AllergyIntolerance_PraRad}}",
                "resource" => [
                    "resourceType" => "AllergyIntolerance",
                    "identifier" => [
                        [
                            "system" =>
                                "http://sys-ids.kemkes.go.id/allergy/{{Org_ID}}",
                            "use" => "official",
                            "value" => "DK2024019917",
                        ],
                    ],
                    "clinicalStatus" => [
                        "coding" => [
                            [
                                "system" =>
                                    "http://terminology.hl7.org/CodeSystem/allergyintolerance-clinical",
                                "code" => "active",
                                "display" => "Active",
                            ],
                        ],
                    ],
                    "verificationStatus" => [
                        "coding" => [
                            [
                                "system" =>
                                    "http://terminology.hl7.org/CodeSystem/allergyintolerance-verification",
                                "code" => "confirmed",
                                "display" => "Confirmed",
                            ],
                        ],
                    ],
                    "category" => ["medication"],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://sys-ids.kemkes.go.id/kfa",
                                "code" => "91000928",
                                "display" => "Barium Sulfate",
                            ],
                        ],
                        "text" => "Alergi Barium Sulfate",
                    ],
                    "patient" => [
                        "reference" => "Patient/{{Patient_ID}}",
                        "display" => "{{Patient_Name}}",
                    ],
                    "encounter" => ["reference" => "urn:uuid:{{Encounter_id}}"],
                    "recordedDate" => "2023-07-04T09:30:00+00:00",
                    "recorder" => [
                        "reference" => "Practitioner/{{Practitioner_ID}}",
                    ],
                ],
                "request" => ["method" => "POST", "url" => "AllergyIntolerance"],
            ],
            [
                "fullUrl" => "urn:uuid:{{ServiceRequest_Rad}}",
                "resource" => [
                    "resourceType" => "ServiceRequest",
                    "identifier" => [
                        [
                            "system" =>
                                "http://sys-ids.kemkes.go.id/servicerequest/{{Org_ID}}",
                            "value" => "DK2024029917",
                        ],
                        [
                            "use" => "usual",
                            "type" => [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://terminology.hl7.org/CodeSystem/v2-0203",
                                        "code" => "ACSN",
                                    ],
                                ],
                            ],
                            "system" =>
                                "http://sys-ids.kemkes.go.id/acsn/{{Org_ID}}",
                            "value" => "P20240001XY",
                        ],
                    ],
                    "status" => "active",
                    "intent" => "original-order",
                    "priority" => "stat",
                    "category" => [
                        [
                            "coding" => [
                                [
                                    "system" => "http://snomed.info/sct",
                                    "code" => "363679005",
                                    "display" => "Imaging",
                                ],
                            ],
                        ],
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://loinc.org",
                                "code" => "11525-3",
                                "display" => "US for pregnancy",
                            ],
                        ],
                    ],
                    "orderDetail" => [
                        [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://dicom.nema.org/resources/ontology/DCM",
                                    "code" => "US",
                                ],
                            ],
                            "text" => "Modality code: US",
                        ],
                        [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://sys-ids.kemkes.go.id/ae-title",
                                    "display" => "US001",
                                ],
                            ],
                        ],
                    ],
                    "subject" => [
                        "reference" => "Patient/{{Patient_ID}}",
                        "display" => "{{Patient_Name}}",
                    ],
                    "encounter" => ["reference" => "urn:uuid:{{Encounter_id}}"],
                    "occurrenceDateTime" => "2023-07-04T09:30:00+00:00",
                    "authoredOn" => "2023-07-04T09:30:00+00:00",
                    "requester" => [
                        "reference" => "Practitioner/{{Practitioner_ID}}",
                        "display" => "{{Practitioner_Name}}",
                    ],
                    "performer" => [
                        [
                            "reference" => "Practitioner/N10000005",
                            "display" => "Fatma",
                        ],
                    ],
                    "bodySite" => [
                        [
                            "coding" => [
                                [
                                    "system" => "http://snomed.info/sct",
                                    "code" => "35039007",
                                    "display" => "Uterine structure",
                                ],
                            ],
                        ],
                    ],
                    "reasonCode" => [
                        [
                            "text" =>
                                "Pemeriksaan USG pada daerah uterus dilakukan untuk mengetahui sebab perdarahan pervagina",
                        ],
                    ],
                    "reasonReference" => [
                        ["reference" => "urn:uuid:{{Condition_DiagnosisAwal}}"],
                    ],
                    "note" => [["text" => "Pemeriksaan USG kehamilan"]],
                    "supportingInfo" => [
                        ["reference" => "urn:uuid:{{Observation_PraRad}}"],
                        ["reference" => "urn:uuid:{{Procedure_PraRad}}"],
                        ["reference" => "urn:uuid:{{AllergyIntolerance_PraRad}}"],
                    ],
                ],
                "request" => ["method" => "POST", "url" => "ServiceRequest"],
            ],
            [
                "fullUrl" => "urn:uuid:{{Observation_Rad}}",
                "resource" => [
                    "resourceType" => "Observation",
                    "identifier" => [
                        [
                            "system" =>
                                "http://sys-ids.kemkes.go.id/observation/{{Org_ID}}",
                            "value" => "DK2024019917",
                        ],
                    ],
                    "status" => "final",
                    "category" => [
                        [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.hl7.org/CodeSystem/observation-category",
                                    "code" => "imaging",
                                    "display" => "Imaging",
                                ],
                            ],
                        ],
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://loinc.org",
                                "code" => "11525-3",
                                "display" => "US for pregnancy",
                            ],
                        ],
                    ],
                    "subject" => ["reference" => "Patient/{{Patient_ID}}"],
                    "encounter" => ["reference" => "urn:uuid:{{Encounter_id}}"],
                    "effectiveDateTime" => "2023-07-04T09:35:00+00:00",
                    "issued" => "2023-07-04T09:35:00+00:00",
                    "performer" => [
                        ["reference" => "Practitioner/{{Practitioner_ID}}"],
                        ["reference" => "Organization/{{Org_ID}}"],
                    ],
                    "basedOn" => [
                        ["reference" => "urn:uuid:{{ServiceRequest_Rad}}"],
                    ],
                    "bodySite" => [
                        "coding" => [
                            [
                                "system" => "http://snomed.info/sct",
                                "code" => "35039007",
                                "display" => "Uterine structure",
                            ],
                        ],
                    ],
                    "derivedFrom" => [
                        [
                            "reference" =>
                                "urn:uuid:2e483a08-6a0e-4ed1-be26-0793fda3d6c2",
                        ],
                    ],
                    "valueString" =>
                        "Ditemukan ada defek dinding uterus dengan uterus kosong, janin berada di luar rongga uterus, plasenta previa, plasenta perkreta, selaput janin menonjol, dan ada cairan bebas di rongga peritoneum",
                ],
                "request" => ["method" => "POST", "url" => "Observation"],
            ],
            [
                "fullUrl" => "urn:uuid:{{DiagnosticReport_Rad}}",
                "resource" => [
                    "resourceType" => "DiagnosticReport",
                    "identifier" => [
                        [
                            "system" =>
                                "http://sys-ids.kemkes.go.id/diagnostic/{{Org_ID}}/rad",
                            "use" => "official",
                            "value" => "DK202401A9917",
                        ],
                    ],
                    "status" => "final",
                    "category" => [
                        [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.hl7.org/CodeSystem/v2-0074",
                                    "code" => "OUS",
                                    "display" => "OB Ultrasound",
                                ],
                            ],
                        ],
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://loinc.org",
                                "code" => "11525-3",
                                "display" => "US for pregnancy",
                            ],
                        ],
                    ],
                    "subject" => ["reference" => "Patient/{{Patient_ID}}"],
                    "encounter" => ["reference" => "urn:uuid:{{Encounter_id}}"],
                    "effectiveDateTime" => "2023-07-04T09:35:00+00:00",
                    "issued" => "2023-07-04T09:35:00+00:00",
                    "performer" => [
                        ["reference" => "Practitioner/{{Practitioner_ID}}"],
                        ["reference" => "Organization/{{Org_ID}}"],
                    ],
                    "imagingStudy" => [
                        [
                            "reference" =>
                                "urn:uuid:2e483a08-6a0e-4ed1-be26-0793fda3d6c2",
                        ],
                    ],
                    "result" => [["reference" => "urn:uuid:{{Observation_Rad}}"]],
                    "basedOn" => [
                        ["reference" => "urn:uuid:{{ServiceRequest_Rad}}"],
                    ],
                    "conclusion" =>
                        "Defek dinding uterus dengan uterus kosong dan janin berada di luar rongga uterus, plasenta previa, plasenta perkreta, selaput janin menonjol, dan ada cairan bebas di rongga peritoneum",
                ],
                "request" => ["method" => "POST", "url" => "DiagnosticReport"],
            ],
            [
                "fullUrl" => "urn:uuid:{{Medication_forRequest}}",
                "resource" => [
                    "resourceType" => "Medication",
                    "meta" => [
                        "profile" => [
                            "https://fhir.kemkes.go.id/r4/StructureDefinition/Medication",
                        ],
                    ],
                    "identifier" => [
                        [
                            "system" =>
                                "http://sys-ids.kemkes.go.id/medication/{{Org_ID}}",
                            "use" => "official",
                            "value" => "DK20240199Z17-1",
                        ],
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://sys-ids.kemkes.go.id/kfa",
                                "code" => "93012760",
                                "display" =>
                                    "Oxytocin 10 IU/mL Injeksi (FRESENIUS KABI COMBIPHAR, 1 mL)",
                            ],
                        ],
                    ],
                    "status" => "active",
                    "manufacturer" => ["reference" => "Organization/900001"],
                    "form" => [
                        "coding" => [
                            [
                                "system" =>
                                    "http://terminology.kemkes.go.id/CodeSystem/medication-form",
                                "code" => "BS034",
                                "display" => "Larutan Injeksi",
                            ],
                        ],
                    ],
                    "ingredient" => [
                        [
                            "itemCodeableConcept" => [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://sys-ids.kemkes.go.id/kfa",
                                        "code" => "91000595",
                                        "display" => "Oxytocin",
                                    ],
                                ],
                            ],
                            "isActive" => true,
                            "strength" => [
                                "numerator" => [
                                    "value" => 10,
                                    "system" => "http://unitsofmeasure.org",
                                    "code" => "[IU]",
                                ],
                                "denominator" => [
                                    "value" => 1,
                                    "system" => "http://unitsofmeasure.org",
                                    "code" => "mL",
                                ],
                            ],
                        ],
                    ],
                    "extension" => [
                        [
                            "url" =>
                                "https://fhir.kemkes.go.id/r4/StructureDefinition/MedicationType",
                            "valueCodeableConcept" => [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://terminology.kemkes.go.id/CodeSystem/medication-type",
                                        "code" => "NC",
                                        "display" => "Non-compound",
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                "request" => ["method" => "POST", "url" => "Medication"],
            ],
            [
                "fullUrl" => "urn:uuid:{{MedicationRequest_id}}",
                "resource" => [
                    "resourceType" => "MedicationRequest",
                    "identifier" => [
                        [
                            "system" =>
                                "http://sys-ids.kemkes.go.id/prescription/{{Org_ID}}",
                            "use" => "official",
                            "value" => "DK1234567899917",
                        ],
                        [
                            "system" =>
                                "http://sys-ids.kemkes.go.id/prescription-item/{{Org_ID}}",
                            "use" => "official",
                            "value" => "DK1234567899917-1",
                        ],
                    ],
                    "status" => "completed",
                    "intent" => "order",
                    "category" => [
                        [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.hl7.org/CodeSystem/medicationrequest-category",
                                    "code" => "outpatient",
                                    "display" => "Outpatient",
                                ],
                            ],
                        ],
                    ],
                    "priority" => "stat",
                    "medicationReference" => [
                        "reference" => "urn:uuid:{{Medication_forRequest}}",
                        "display" =>
                            "Oxytocin 10 IU/mL Injeksi (FRESENIUS KABI COMBIPHAR, 1 mL)",
                    ],
                    "subject" => [
                        "reference" => "Patient/{{Patient_ID}}",
                        "display" => "{{Patient_Name}}",
                    ],
                    "encounter" => ["reference" => "urn:uuid:{{Encounter_id}}"],
                    "authoredOn" => "2023-07-04T10:00:00+00:00",
                    "requester" => [
                        "reference" => "Practitioner/{{Practitioner_ID}}",
                        "display" => "{{Practitioner_Name}}",
                    ],
                    "reasonReference" => [
                        ["reference" => "urn:uuid:{{Condition_DiagnosisAwal}}"],
                    ],
                    "dosageInstruction" => [
                        [
                            "sequence" => 1,
                            "patientInstruction" =>
                                "1 kali 10 IU/mL injeksi segera pasca tindakan sectio caesaria",
                            "additionalInstruction" => [
                                [
                                    "coding" => [
                                        [
                                            "system" => "http://snomed.info/sct",
                                            "code" => "421769005",
                                            "display" => "Follow directions",
                                        ],
                                    ],
                                ],
                            ],
                            "timing" => [
                                "repeat" => [
                                    "frequency" => 1,
                                    "period" => 1,
                                    "periodUnit" => "d",
                                ],
                            ],
                            "route" => [
                                "coding" => [
                                    [
                                        "system" => "http://www.whocc.no/atc",
                                        "code" => "inj.intramuscular",
                                        "display" => "Injection Intramuscular",
                                    ],
                                ],
                            ],
                            "doseAndRate" => [
                                [
                                    "type" => [
                                        "coding" => [
                                            [
                                                "system" =>
                                                    "http://terminology.hl7.org/CodeSystem/dose-rate-type",
                                                "code" => "ordered",
                                                "display" => "Ordered",
                                            ],
                                        ],
                                    ],
                                    "doseQuantity" => [
                                        "value" => 10,
                                        "unit" => "IU",
                                        "system" => "http://unitsofmeasure.org",
                                        "code" => "[IU]",
                                    ],
                                ],
                            ],
                        ],
                    ],
                    "dispenseRequest" => [
                        "dispenseInterval" => [
                            "value" => 1,
                            "unit" => "days",
                            "system" => "http://unitsofmeasure.org",
                            "code" => "d",
                        ],
                        "validityPeriod" => [
                            "start" => "2023-07-04T10:00:00+00:00",
                            "end" => "2023-07-04T10:00:00+00:00",
                        ],
                        "numberOfRepeatsAllowed" => 0,
                        "quantity" => [
                            "value" => 1,
                            "unit" => "Ampule - unit of product usage",
                            "system" => "http://snomed.info/sct",
                            "code" => "413516001",
                        ],
                        "expectedSupplyDuration" => [
                            "value" => 1,
                            "unit" => "days",
                            "system" => "http://unitsofmeasure.org",
                            "code" => "d",
                        ],
                        "performer" => ["reference" => "Organization/{{Org_ID}}"],
                    ],
                ],
                "request" => ["method" => "POST", "url" => "MedicationRequest"],
            ],
            [
                "fullUrl" => "urn:uuid:{{QuestionnaireResponse_KajianResep}}",
                "resource" => [
                    "resourceType" => "QuestionnaireResponse",
                    "questionnaire" =>
                        "https://fhir.kemkes.go.id/Questionnaire/Q0007",
                    "status" => "completed",
                    "subject" => [
                        "reference" => "Patient/{{Patient_ID}}",
                        "display" => "{{Patient_Name}}",
                    ],
                    "encounter" => ["reference" => "urn:uuid:{{Encounter_id}}"],
                    "authored" => "2023-07-04T11:00:00+00:00",
                    "author" => ["reference" => "Practitioner/{{Practitioner_ID}}"],
                    "source" => ["reference" => "Patient/{{Patient_ID}}"],
                    "item" => [
                        [
                            "linkId" => "1",
                            "text" => "Persyaratan Administrasi",
                            "item" => [
                                [
                                    "linkId" => "1.1",
                                    "text" =>
                                        "Apakah nama, umur, jenis kelamin, berat badan dan tinggi badan pasien sudah sesuai?",
                                    "answer" => [
                                        [
                                            "valueCoding" => [
                                                "system" =>
                                                    "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
                                                "code" => "OV000052",
                                                "display" => "Sesuai",
                                            ],
                                        ],
                                    ],
                                ],
                                [
                                    "linkId" => "1.2",
                                    "text" =>
                                        "Apakah nama, nomor ijin, alamat dan paraf dokter sudah sesuai?",
                                    "answer" => [
                                        [
                                            "valueCoding" => [
                                                "system" =>
                                                    "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
                                                "code" => "OV000052",
                                                "display" => "Sesuai",
                                            ],
                                        ],
                                    ],
                                ],
                                [
                                    "linkId" => "1.3",
                                    "text" => "Apakah tanggal resep sudah sesuai?",
                                    "answer" => [
                                        [
                                            "valueCoding" => [
                                                "system" =>
                                                    "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
                                                "code" => "OV000053",
                                                "display" => "Tidak Sesuai",
                                            ],
                                        ],
                                    ],
                                ],
                                [
                                    "linkId" => "1.4",
                                    "text" =>
                                        "Apakah ruangan/unit asal resep sudah sesuai?",
                                    "answer" => [
                                        [
                                            "valueCoding" => [
                                                "system" =>
                                                    "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
                                                "code" => "OV000052",
                                                "display" => "Sesuai",
                                            ],
                                        ],
                                    ],
                                ],
                                [
                                    "linkId" => "2",
                                    "text" => "Persyaratan Farmasetik",
                                    "item" => [
                                        [
                                            "linkId" => "2.1",
                                            "text" =>
                                                "Apakah nama obat, bentuk dan kekuatan sediaan sudah sesuai?",
                                            "answer" => [
                                                [
                                                    "valueCoding" => [
                                                        "system" =>
                                                            "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
                                                        "code" => "OV000052",
                                                        "display" => "Sesuai",
                                                    ],
                                                ],
                                            ],
                                        ],
                                        [
                                            "linkId" => "2.2",
                                            "text" =>
                                                "Apakah dosis dan jumlah obat sudah sesuai?",
                                            "answer" => [
                                                [
                                                    "valueCoding" => [
                                                        "system" =>
                                                            "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
                                                        "code" => "OV000052",
                                                        "display" => "Sesuai",
                                                    ],
                                                ],
                                            ],
                                        ],
                                        [
                                            "linkId" => "2.3",
                                            "text" =>
                                                "Apakah stabilitas obat sudah sesuai?",
                                            "answer" => [
                                                [
                                                    "valueCoding" => [
                                                        "system" =>
                                                            "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
                                                        "code" => "OV000053",
                                                        "display" => "Tidak Sesuai",
                                                    ],
                                                ],
                                            ],
                                        ],
                                        [
                                            "linkId" => "2.4",
                                            "text" =>
                                                "Apakah aturan dan cara penggunaan obat sudah sesuai?",
                                            "answer" => [
                                                [
                                                    "valueCoding" => [
                                                        "system" =>
                                                            "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
                                                        "code" => "OV000052",
                                                        "display" => "Sesuai",
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                                [
                                    "linkId" => "3",
                                    "text" => "Persyaratan Klinis",
                                    "item" => [
                                        [
                                            "linkId" => "3.1",
                                            "text" =>
                                                "Apakah ketepatan indikasi, dosis, dan waktu penggunaan obat sudah sesuai?",
                                            "answer" => [
                                                [
                                                    "valueCoding" => [
                                                        "system" =>
                                                            "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
                                                        "code" => "OV000052",
                                                        "display" => "Sesuai",
                                                    ],
                                                ],
                                            ],
                                        ],
                                        [
                                            "linkId" => "3.2",
                                            "text" =>
                                                "Apakah terdapat duplikasi pengobatan?",
                                            "answer" => [["valueBoolean" => false]],
                                        ],
                                        [
                                            "linkId" => "3.3",
                                            "text" =>
                                                "Apakah terdapat alergi dan reaksi obat yang tidak dikehendaki (ROTD)?",
                                            "answer" => [["valueBoolean" => false]],
                                        ],
                                        [
                                            "linkId" => "3.4",
                                            "text" =>
                                                "Apakah terdapat kontraindikasi pengobatan?",
                                            "answer" => [["valueBoolean" => false]],
                                        ],
                                        [
                                            "linkId" => "3.5",
                                            "text" =>
                                                "Apakah terdapat dampak interaksi obat?",
                                            "answer" => [["valueBoolean" => true]],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                "request" => ["method" => "POST", "url" => "QuestionnaireResponse"],
            ],
            [
                "fullUrl" => "urn:uuid:{{Medication_forDispense}}",
                "resource" => [
                    "resourceType" => "Medication",
                    "meta" => [
                        "profile" => [
                            "https://fhir.kemkes.go.id/r4/StructureDefinition/Medication",
                        ],
                    ],
                    "identifier" => [
                        [
                            "system" =>
                                "http://sys-ids.kemkes.go.id/medication/{{Org_ID}}",
                            "use" => "official",
                            "value" => "DK12345678899917",
                        ],
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://sys-ids.kemkes.go.id/kfa",
                                "code" => "93012760",
                                "display" =>
                                    "Oxytocin 10 IU/mL Injeksi (FRESENIUS KABI COMBIPHAR, 1 mL)",
                            ],
                        ],
                    ],
                    "status" => "active",
                    "manufacturer" => ["reference" => "Organization/900001"],
                    "form" => [
                        "coding" => [
                            [
                                "system" =>
                                    "http://terminology.kemkes.go.id/CodeSystem/medication-form",
                                "code" => "BS034",
                                "display" => "Larutan Injeksi",
                            ],
                        ],
                    ],
                    "batch" => [
                        "lotNumber" => "1625042A",
                        "expirationDate" => "2026-08-28",
                    ],
                    "ingredient" => [
                        [
                            "itemCodeableConcept" => [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://sys-ids.kemkes.go.id/kfa",
                                        "code" => "91000595",
                                        "display" => "Oxytocin",
                                    ],
                                ],
                            ],
                            "isActive" => true,
                            "strength" => [
                                "numerator" => [
                                    "value" => 10,
                                    "system" => "http://unitsofmeasure.org",
                                    "code" => "[IU]",
                                ],
                                "denominator" => [
                                    "value" => 1,
                                    "system" => "http://unitsofmeasure.org",
                                    "code" => "mL",
                                ],
                            ],
                        ],
                    ],
                    "extension" => [
                        [
                            "url" =>
                                "https://fhir.kemkes.go.id/r4/StructureDefinition/MedicationType",
                            "valueCodeableConcept" => [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://terminology.kemkes.go.id/CodeSystem/medication-type",
                                        "code" => "NC",
                                        "display" => "Non-compound",
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                "request" => ["method" => "POST", "url" => "Medication"],
            ],
            [
                "fullUrl" => "urn:uuid:{{MedicationDispense_id}}",
                "resource" => [
                    "resourceType" => "MedicationDispense",
                    "identifier" => [
                        [
                            "system" =>
                                "http://sys-ids.kemkes.go.id/prescription/{{Org_ID}}",
                            "use" => "official",
                            "value" => "DK12345678900889917",
                        ],
                        [
                            "system" =>
                                "http://sys-ids.kemkes.go.id/prescription-item/{{Org_ID}}",
                            "use" => "official",
                            "value" => "DK12345678899917-1",
                        ],
                    ],
                    "status" => "completed",
                    "category" => [
                        "coding" => [
                            [
                                "system" =>
                                    "http://terminology.hl7.org/fhir/CodeSystem/medicationdispense-category",
                                "code" => "outpatient",
                                "display" => "Outpatient",
                            ],
                        ],
                    ],
                    "medicationReference" => [
                        "reference" => "urn:uuid:{{Medication_forDispense}}",
                        "display" =>
                            "Oxytocin 10 IU/mL Injeksi (FRESENIUS KABI COMBIPHAR, 1 mL)",
                    ],
                    "subject" => [
                        "reference" => "Patient/{{Patient_ID}}",
                        "display" => "{{Patient_Name}}",
                    ],
                    "context" => ["reference" => "urn:uuid:{{Encounter_id}}"],
                    "performer" => [
                        [
                            "actor" => [
                                "reference" => "Practitioner/{{Practitioner_ID}}",
                                "display" => "{{Practitioner_Name}}",
                            ],
                        ],
                    ],
                    "location" => [
                        "reference" => "Location/{{Location_RTK}}",
                        "display" =>
                            "Ruangan Tindakan Kebidanan, Instalasi Gawat Darurat, Gedung Utama, Lantai 1",
                    ],
                    "authorizingPrescription" => [
                        ["reference" => "urn:uuid:{{MedicationRequest_id}}"],
                    ],
                    "quantity" => [
                        "value" => 1,
                        "unit" => "Ampule - unit of product usage",
                        "system" => "http://snomed.info/sct",
                        "code" => "413516001",
                    ],
                    "daysSupply" => [
                        "value" => 1,
                        "unit" => "Day",
                        "system" => "http://unitsofmeasure.org",
                        "code" => "d",
                    ],
                    "whenPrepared" => "2023-07-04T10:15:00+00:00",
                    "whenHandedOver" => "2023-07-04T10:15:00+00:00",
                    "dosageInstruction" => [
                        [
                            "sequence" => 1,
                            "patientInstruction" =>
                                "1 kali 10 IU/mL injeksi segera pasca tindakan sectio caesaria",
                            "additionalInstruction" => [
                                [
                                    "coding" => [
                                        [
                                            "system" => "http://snomed.info/sct",
                                            "code" => "421769005",
                                            "display" => "Follow directions",
                                        ],
                                    ],
                                ],
                            ],
                            "timing" => [
                                "repeat" => [
                                    "frequency" => 1,
                                    "period" => 1,
                                    "periodUnit" => "d",
                                ],
                            ],
                            "route" => [
                                "coding" => [
                                    [
                                        "system" => "http://www.whocc.no/atc",
                                        "code" => "inj.intramuscular",
                                        "display" => "Injection Intramuscular",
                                    ],
                                ],
                            ],
                            "doseAndRate" => [
                                [
                                    "type" => [
                                        "coding" => [
                                            [
                                                "system" =>
                                                    "http://terminology.hl7.org/CodeSystem/dose-rate-type",
                                                "code" => "ordered",
                                                "display" => "Ordered",
                                            ],
                                        ],
                                    ],
                                    "doseQuantity" => [
                                        "value" => 10,
                                        "unit" => "IU",
                                        "system" => "http://unitsofmeasure.org",
                                        "code" => "[IU]",
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                "request" => ["method" => "POST", "url" => "MedicationDispense"],
            ],
            [
                "fullUrl" => "urn:uuid:{{Observation_RencanaPulang}}",
                "resource" => [
                    "resourceType" => "Observation",
                    "status" => "final",
                    "category" => [
                        [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.hl7.org/CodeSystem/observation-category",
                                    "code" => "survey",
                                    "display" => "Survey",
                                ],
                            ],
                        ],
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" =>
                                    "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
                                "code" => "OC000055",
                                "display" =>
                                    "Kriteria Pasien yang dilakukan Rencana Pemulangan",
                            ],
                        ],
                    ],
                    "subject" => ["reference" => "Patient/{{Patient_ID}}"],
                    "encounter" => ["reference" => "urn:uuid:{{Encounter_id}}"],
                    "effectiveDateTime" => "2023-07-04T13:54:00+00:00",
                    "issued" => "2023-07-04T13:54:00+00:00",
                    "performer" => [
                        ["reference" => "Practitioner/{{Practitioner_ID}}"],
                    ],
                    "valueCodeableConcept" => [
                        "coding" => [
                            [
                                "system" =>
                                    "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
                                "code" => "OV000072",
                                "display" =>
                                    "Pasien dengan perawatan berkelanjutan atau panjang",
                            ],
                        ],
                    ],
                ],
                "request" => ["method" => "POST", "url" => "Observation"],
            ],
            [
                "fullUrl" => "urn:uuid:{{CarePlan_RencanaPulang}}",
                "resource" => [
                    "resourceType" => "CarePlan",
                    "title" => "Perencanaan Pemulangan Pasien",
                    "status" => "active",
                    "category" => [
                        [
                            "coding" => [
                                [
                                    "system" => "http://snomed.info/sct",
                                    "code" => "736372004",
                                    "display" => "Discharge care plan",
                                ],
                            ],
                        ],
                    ],
                    "intent" => "plan",
                    "description" =>
                        "Rencana pemulangan: kontrol kembali ke dokter spesialis obstetri dan ginekologi 2 minggu pasca rawat inap",
                    "subject" => [
                        "reference" => "Patient/{{Patient_ID}}",
                        "display" => "{{Patient_Name}}",
                    ],
                    "encounter" => ["reference" => "urn:uuid:{{Encounter_id}}"],
                    "created" => "2023-07-04T13:50:00+00:00",
                    "author" => ["reference" => "Practitioner/{{Practitioner_ID}}"],
                ],
                "request" => ["method" => "POST", "url" => "CarePlan"],
            ],
        ],
    ];
    
    }
}
