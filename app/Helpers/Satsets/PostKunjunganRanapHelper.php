<?php

namespace App\Helpers\Satsets;

use App\Helpers\BridgingSatsetHelper;
use Carbon\Carbon;
use Illuminate\Support\Str;

class PostKunjunganRanapHelper
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


    static function encounter($request, $pasien_uuid, $organization_id)
    {

        // setlocale(LC_ALL, 'IND');
      $start = Carbon::parse($request['tglmasuk'])->toIso8601String();
      $end = Carbon::parse($request['tglkeluar'])->toIso8601String();

      $encounter = self::generateUuid();

      $basedOn = self::generateUuid();

      // DIAGNOSA

      $diagnosa = [];
      foreach ($request->diagnosa as $key => $value) {
          $uuid = self::generateUuid();
          $data = [
            "condition" => [
                    "reference" => "urn:uuid:$uuid",
                    "display" => $value['inggris'] ?? '-',
                ],
                "use" => [
                    "coding" => [
                        [
                            "system" => "http://terminology.hl7.org/CodeSystem/diagnosis-role",
                            "code" => "DD",
                            "display" => "Discharge diagnosis",
                        ],
                    ],
                ],
                "rank" => $key + 1,
          ];

          $diagnosa[] = $data;
      }

      $naikKelas = $request->hak_kelas!==null? (
        (int) $request->kelasruangan > (int) $request->hak_kelas ? "Kelas Naik" : "Kelas Tetap"
      ): "Kelas Tetap";


      $relmasterRuang = $request->relmasterruangranap['ruang'];
      $ruangId = !$relmasterRuang ? '-': $relmasterRuang['satset_uuid'] ?? '-';
      $ruang = !$relmasterRuang ? '-': $relmasterRuang['ruang'] ?? '-';
      $lantai = !$relmasterRuang ? '-': $relmasterRuang['lantai'] ?? '-';
      $gedung = !$relmasterRuang ? '-': $relmasterRuang['gedung'] ?? '-';


        // MULAI BUAT FORM


        $formEncounter = [
          "fullUrl" => "urn:uuid:$encounter",
          "resource" => [
              "resourceType" => "Encounter",
              "identifier" => [
                  [
                      "system" =>
                          "http://sys-ids.kemkes.go.id/encounter/$organization_id",
                      "value" => "$pasien_uuid",
                  ],
              ],
              "status" => "finished",
              "statusHistory" => [
                  [
                      "status" => "in-progress",
                      "period" => [
                          "start" => $start,
                          "end" => $end,
                      ],
                  ],
                  [
                      "status" => "finished",
                      "period" => [
                          "start" => $end,
                          "end" => $end,
                      ],
                  ],
              ],
              "class" => [
                  "system" =>
                      "http://terminology.hl7.org/CodeSystem/v3-ActCode",
                  "code" => "IMP",
                  "display" => "inpatient encounter",
              ],
              "subject" => [
                  "reference" => "Patient/$pasien_uuid",
                  "display" => $request->nama_panggil,
              ],
              "basedOn" => [
                  [
                      "reference" => "urn:uuid:$basedOn",
                  ],
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
                          "reference" => "Practitioner/".$request->datasimpeg['satset_uuid'],
                          "display" => $request->datasimpeg['nama'],
                      ],
                  ],
              ],
              "period" => [
                  "start" => $start,
                  "end" => $end,
              ],
              "diagnosis" => $diagnosa,
              "hospitalization" => [
                  "dischargeDisposition" => [
                      "coding" => [
                          [
                              "system" => "http://terminology.hl7.org/CodeSystem/discharge-disposition",
                              "code" => "home",
                              "display" => "Home",
                          ],
                      ],
                      "text" => !empty($request->tindaklanjut) ? "Anjuran dokter untuk pulang dan $request->tindaklanjut" : "Anjuran dokter untuk pulang ",
                  ],
              ],
              "location" => [
                  [
                      "extension" => [
                          [
                              "extension" => [
                                  [
                                      "url" => "value",
                                      "valueCodeableConcept" => [
                                          "coding" => [
                                              [
                                                  "system" =>"http://terminology.kemkes.go.id/CodeSystem/locationServiceClass-Inpatient",
                                                  "code" => "$request->kelasruangan",
                                                  "display" => "Kelas $request->kelasruangan",
                                              ],
                                          ],
                                      ],
                                  ],
                                  // ini jika ada kenaikan kelas
                                  [
                                      "url" => "upgradeClassIndicator",
                                      "valueCodeableConcept" => [
                                          "coding" => [
                                              [
                                                  "system" =>"http://terminology.kemkes.go.id/CodeSystem/locationUpgradeClass",
                                                  "code" => $naikKelas === "Kelas Naik" ? "kelas-naik" : "kelas-tetap",
                                                  "display" => "$naikKelas Perawatan",
                                              ],
                                          ],
                                      ],
                                  ],
                              ],
                              "url" =>
                                  "https://fhir.kemkes.go.id/r4/StructureDefinition/ServiceClass",
                          ],
                      ],
                      "location" => [
                          "reference" => "Location/" .$ruangId,
                          "display" =>
                              "Bed $request->nomorbed, $request->group_ruangan, $request->ruangan, Layanan Rawat Inap, Lantai $lantai Gedung $gedung",
                      ],
                      "period" => [
                          "start" => $start,
                          "end" => $end,
                      ],
                  ],
              ],
              "serviceProvider" => ["reference" => "Organization/$organization_id"],
          ],
          "request" => ["method" => "POST", "url" => "Encounter"],
        ];



        // 2. Condition
        $formCondition=null;
        if (count($diagnosa) > 0) {
            $formCondition = self::condition($request, $pasien_uuid, $organization_id, $diagnosa, $encounter);
          }

        

        $form =[
            "encounter" => $formEncounter,
            "condition" => $formCondition
        ];
        return $form;
       
    }

    static function condition($request, $pasien_uuid, $organization_id, $diagnosa, $encounter)
    {

        $conditions = [];
        foreach ($request->diagnosa as $key => $value) {
            $cond = 
            [
                // "fullUrl" => "urn:uuid:{{Condition_DiagnosisPrimer}}",
                "fullUrl" => $diagnosa[$key]['condition']['reference'],
                "resource" => [
                    "resourceType" => "Condition",
                    "clinicalStatus" => [
                        "coding" => [
                            [
                                "system" => "http://terminology.hl7.org/CodeSystem/condition-clinical",
                                "code" => "active",
                                "display" => "Active",
                            ],
                        ],
                    ],
                    "category" => [
                        [
                            "coding" => [
                                [
                                    "system" => "http://terminology.hl7.org/CodeSystem/condition-category",
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
                                "code" => $value['kode'],
                                "display" => $value['inggris'],
                            ],
                        ],
                    ],
                    "subject" => [
                        "reference" => "Patient/$pasien_uuid",
                        "display" => $request->nama_panggil,
                    ],
                    "encounter" => ["reference" => "urn:uuid:$encounter"],
                    "onsetDateTime" => Carbon::parse($value['recordedDate'])->toIso8601String(),
                    "recordedDate" => Carbon::parse($value['recordedDate'])->toIso8601String(),
                    "note" => [["text" => "Pasien mengalami ".$value['indonesia']]],
                ],
                "request" => ["method" => "POST", "url" => "Condition"],
            ];

            $conditions[] = $cond;
        }
        


        return $conditions;
    }



    public function ygHarusDikerjakan()
    {
      $arrayVar = [
        "resourceType" => "Bundle",
        "type" => "transaction",
        "entry" => [

          // 1. Encounter dikerjakan
            [
                "fullUrl" => "urn:uuid:{{Encounter_id}}",
                "resource" => [
                    "resourceType" => "Encounter",
                    "identifier" => [
                        [
                            "system" =>
                                "http://sys-ids.kemkes.go.id/encounter/{{Org_id}}",
                            "value" => "{{Registration_ID}}",
                        ],
                    ],
                    "status" => "finished",
                    "statusHistory" => [
                        [
                            "status" => "in-progress",
                            "period" => [
                                "start" => "2022-12-25T08:00:00+00:00",
                                "end" => "2022-12-30T09:30:27+07:00",
                            ],
                        ],
                        [
                            "status" => "finished",
                            "period" => [
                                "start" => "2022-12-30T09:30:27+07:00",
                                "end" => "2022-12-30T09:30:27+07:00",
                            ],
                        ],
                    ],
                    "class" => [
                        "system" =>
                            "http://terminology.hl7.org/CodeSystem/v3-ActCode",
                        "code" => "IMP",
                        "display" => "inpatient encounter",
                    ],
                    "subject" => [
                        "reference" => "Patient/{{Patient_Id}}",
                        "display" => "{{Patient_Name}}",
                    ],
                    "basedOn" => [
                        [
                            "reference" =>
                                "urn:uuid:1e1a260d-538f-4172-ad68-0aa5f8ccfc4a",
                        ],
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
                                "reference" => "Practitioner/{{Practitioner_Id}}",
                                "display" => "{{Practitioner_Name}}",
                            ],
                        ],
                    ],
                    "period" => [
                        "start" => "2022-12-25T08:00:00+00:00",
                        "end" => "2022-12-30T09:30:27+07:00",
                    ],
                    "diagnosis" => [
                        [
                            "condition" => [
                                "reference" =>
                                    "urn:uuid:{{Condition_DiagnosisPrimer}}",
                                "display" => "Chronic kidney disease, stage 5",
                            ],
                            "use" => [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://terminology.hl7.org/CodeSystem/diagnosis-role",
                                        "code" => "DD",
                                        "display" => "Discharge diagnosis",
                                    ],
                                ],
                            ],
                            "rank" => 1,
                        ],
                        [
                            "condition" => [
                                "reference" =>
                                    "urn:uuid:{{Condition_DiagnosisSekunder}}",
                                "display" => "Anemia in chronic kidney disease",
                            ],
                            "use" => [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://terminology.hl7.org/CodeSystem/diagnosis-role",
                                        "code" => "DD",
                                        "display" => "Discharge diagnosis",
                                    ],
                                ],
                            ],
                            "rank" => 2,
                        ],
                    ],
                    "hospitalization" => [
                        "dischargeDisposition" => [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.hl7.org/CodeSystem/discharge-disposition",
                                    "code" => "home",
                                    "display" => "Home",
                                ],
                            ],
                            "text" =>
                                "Anjuran dokter untuk pulang dan kontrol kembali dan melakukan hemodialisis Rutin 1 Bulan Sekali",
                        ],
                    ],
                    "location" => [
                        [
                            "extension" => [
                                [
                                    "extension" => [
                                        [
                                            "url" => "value",
                                            "valueCodeableConcept" => [
                                                "coding" => [
                                                    [
                                                        "system" =>
                                                            "http://terminology.kemkes.go.id/CodeSystem/locationServiceClass-Inpatient",
                                                        "code" => "1",
                                                        "display" => "Kelas 1",
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
                                    "url" =>
                                        "https://fhir.kemkes.go.id/r4/StructureDefinition/ServiceClass",
                                ],
                            ],
                            "location" => [
                                "reference" =>
                                    "Location/{{Location_Ruang210_Bed2_id}}",
                                "display" =>
                                    "Bed 2, Ruang 210, Bangsal Rawat Inap Kelas 1, Layanan Penyakit Dalam, Lantai 2, Gedung Utama",
                            ],
                            "period" => [
                                "start" => "2022-12-25T08:00:00+00:00",
                                "end" => "2022-12-30T09:30:27+07:00",
                            ],
                        ],
                    ],
                    "serviceProvider" => ["reference" => "Organization/{{Org_id}}"],
                ],
                "request" => ["method" => "POST", "url" => "Encounter"],
            ],

            // 2. CarePlan kebawah belum dikerjakan
            [
                "fullUrl" => "urn:uuid:{{CarePlan_RencanaRawat_id}}",
                "resource" => [
                    "resourceType" => "CarePlan",
                    "status" => "active",
                    "intent" => "plan",
                    "category" => [
                        [
                            "coding" => [
                                [
                                    "system" => "http://snomed.info/sct",
                                    "code" => "736353004",
                                    "display" => " Inpatient care plan",
                                ],
                            ],
                        ],
                    ],
                    "title" => "Rencana Rawat Pasien",
                    "description" =>
                        "Pasien akan melakukan Pengecekan Kolesterol Darah dan Proses CT-Scan serta Tindakan Hemodialisis dengan Rencana Lama Waktu Rawat selama 3-4 Hari",
                    "subject" => [
                        "reference" => "Patient/{{Patient_Id}}",
                        "display" => "{{Patient_Name}}",
                    ],
                    "encounter" => ["reference" => "urn:uuid:{{Encounter_id}}"],
                    "created" => "2022-12-25T08:00:00+00:00",
                    "author" => ["reference" => "Practitioner/{{Practitioner_Id}}"],
                ],
                "request" => ["method" => "POST", "url" => "CarePlan"],
            ],

            // 3. Observation
            [
                "fullUrl" => "urn:uuid:{{Observation_Kesadaran}}",
                "resource" => [
                    "resourceType" => "Observation",
                    "status" => "final",
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
                    "subject" => ["reference" => "Patient/{{Patient_Id}}"],
                    "encounter" => [
                        "reference" => "urn:uuid:{{Encounter_id}}",
                        "display" =>
                            "Pemeriksaan Kesadaran {{Patient_Name}} di hari Kamis, 22 Desember 2022",
                    ],
                    "effectiveDateTime" => "2022-12-22T08:00:00+00:00",
                    "issued" => "2022-12-22T08:00:00+00:00",
                    "performer" => [
                        ["reference" => "Practitioner/{{Practitioner_Id}}"],
                    ],
                    "valueCodeableConcept" => [
                        "coding" => [
                            [
                                "system" => "http://snomed.info/sct",
                                "code" => "248234008",
                                "display" => "Mentally alert",
                            ],
                        ],
                    ],
                ],
                "request" => ["method" => "POST", "url" => "Observation"],
            ],

            // 4. Observation
            [
                "fullUrl" => "urn:uuid:{{Observation_Nadi}}",
                "resource" => [
                    "resourceType" => "Observation",
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
                    "subject" => ["reference" => "Patient/{{Patient_Id}}"],
                    "encounter" => [
                        "reference" => "urn:uuid:{{Encounter_id}}",
                        "display" =>
                            "Pemeriksaan Fisik Nadi {{Patient_Name}} di hari Selasa, 22 Desember 2022",
                    ],
                    "effectiveDateTime" => "2022-12-22T08:00:00+00:00",
                    "issued" => "2022-12-22T08:00:00+00:00",
                    "performer" => [
                        ["reference" => "Practitioner/{{Practitioner_Id}}"],
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

            // 5. CarePlan
            [
                "fullUrl" => "urn:uuid:{{CarePlan_Instruksi}}",
                "resource" => [
                    "resourceType" => "CarePlan",
                    "status" => "active",
                    "intent" => "plan",
                    "category" => [
                        [
                            "coding" => [
                                [
                                    "system" => "http://snomed.info/sct",
                                    "code" => "736353004",
                                    "display" => " Inpatient care plan",
                                ],
                            ],
                        ],
                    ],
                    "title" => "Instruksi Medik dan Keperawatan Pasien",
                    "description" =>
                        "Penanganan Anemia Pasien dilakukan dengan pemberian hormone eritropoitin, transfusi darah, dan vitamin.",
                    "subject" => [
                        "reference" => "Patient/{{Patient_Id}}",
                        "display" => "{{Patient_Name}}",
                    ],
                    "encounter" => ["reference" => "urn:uuid:{{Encounter_id}}"],
                    "created" => "2022-12-25T08:00:00+00:00",
                    "author" => ["reference" => "Practitioner/{{Practitioner_Id}}"],
                ],
                "request" => ["method" => "POST", "url" => "CarePlan"],
            ],

            // 6. Procedure
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
                        "reference" => "Patient/{{Patient_Id}}",
                        "display" => "{{Patient_Name}}",
                    ],
                    "encounter" => ["reference" => "urn:uuid:{{Encounter_id}}"],
                    "performedPeriod" => [
                        "start" => "2022-12-26T00:00:00+00:00",
                        "end" => "2022-12-26T00:00:00+00:00",
                    ],
                    "performer" => [
                        [
                            "actor" => [
                                "reference" => "Practitioner/{{Practitioner_Id}}",
                                "display" => "{{Practitioner_Name}}",
                            ],
                        ],
                    ],
                    "note" => [["text" => "Prosedur Puasa tidak dilakukan Pasien"]],
                ],
                "request" => ["method" => "POST", "url" => "Procedure"],
            ],

            // 7. ServiceRequest
            [
                "fullUrl" => "urn:uuid:{{ServiceRequest_Lab}}",
                "resource" => [
                    "resourceType" => "ServiceRequest",
                    "identifier" => [
                        [
                            "system" =>
                                "http://sys-ids.kemkes.go.id/servicerequest/{{Org_id}}",
                            "value" => "00001A",
                        ],
                    ],
                    "status" => "active",
                    "intent" => "original-order",
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
                    "priority" => "routine",
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://loinc.org",
                                "code" => "2093-3",
                                "display" =>
                                    "Cholesterol [Mass/volume] in Serum or Plasma",
                            ],
                        ],
                        "text" => "Kolesterol Total",
                    ],
                    "subject" => ["reference" => "Patient/{{Patient_Id}}"],
                    "encounter" => [
                        "reference" => "urn:uuid:{{Encounter_id}}",
                        "display" =>
                            "Permintaan Pemeriksaan Kolesterol Total Jum\'at, 26 Desember 2022 pukul 09:30 WIB",
                    ],
                    "occurrenceDateTime" => "2022-12-26T16:30:00+00:00",
                    "authoredOn" => "2022-12-25T14:00:00+00:00",
                    "requester" => [
                        "reference" => "Practitioner/{{Practitioner_Id}}",
                        "display" => "{{Practitioner_Name}}",
                    ],
                    "performer" => [
                        ["reference" => "Practitioner/{{Practitioner_Lab_Id}}"],
                    ],
                    "reasonCode" => [
                        [
                            "text" =>
                                "Periksa Kolesterol Darah untuk Pelayanan Rawat Inap Pasien a.n {{Patient_Name}}",
                        ],
                    ],
                    "reasonReference" => [
                        ["reference" => "urn:uuid:{{Condition_DiagnosisPrimer}}"],
                    ],
                    "supportingInfo" => [
                        ["reference" => "urn:uuid:{{Procedure_PraLab}}"],
                    ],
                    "note" => [
                        ["text" => "Pasien diminta untuk berpuasa terlebih dahulu"],
                    ],
                ],
                "request" => ["method" => "POST", "url" => "ServiceRequest"],
            ],

            // 8. Specimen
            [
                "fullUrl" => "urn:uuid:{{Specimen_Lab}}",
                "resource" => [
                    "resourceType" => "Specimen",
                    "extension" => [
                        [
                            "url" =>
                                "https://fhir.kemkes.go.id/r4/StructureDefinition/TransportedTime",
                            "valueDateTime" => "2022-12-26T15:15:00+00:00",
                        ],
                        [
                            "url" =>
                                "https://fhir.kemkes.go.id/r4/StructureDefinition/TransportedPerson",
                            "valueContactDetail" => [
                                "name" => "Burhan",
                                "telecom" => [
                                    ["system" => "phone", "value" => "021-5375162"],
                                ],
                            ],
                        ],
                        [
                            "url" =>
                                "https://fhir.kemkes.go.id/r4/StructureDefinition/ReceivedPerson",
                            "valueReference" => [
                                "reference" => "Practitioner/{{Practitioner_Id}}",
                                "display" => "Dr. John Doe",
                            ],
                        ],
                    ],
                    "identifier" => [
                        [
                            "system" =>
                                "http://sys-ids.kemkes.go.id/specimen/{{Org_id}}",
                            "value" => "P20240001",
                            "assigner" => [
                                "reference" => "Organization/{{Org_id}}",
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
                    "subject" => [
                        "reference" => "Patient/{{Patient_Id}}",
                        "display" => "{{Patient_Name}}",
                    ],
                    "receivedTime" => "2022-12-26T15:25:00+00:00",
                    "request" => [
                        ["reference" => "urn:uuid:{{ServiceRequest_Lab}}"],
                    ],
                    "collection" => [
                        "collector" => [
                            "reference" => "Practitioner/{{Practitioner_Lab_Id}}",
                        ],
                        "collectedDateTime" => "2022-12-26T15:00:00+00:00",
                        "quantity" => ["value" => 6, "unit" => "mL"],
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
                        ["timeDateTime" => "2022-12-27T16:30:00+00:00"],
                    ],
                    "condition" => [["text" => "Kondisi Spesimen Baik"]],
                ],
                "request" => ["method" => "POST", "url" => "Specimen"],
            ],

            // 9. Observation
            [
                "fullUrl" => "urn:uuid:{{Observation_Lab}}",
                "resource" => [
                    "resourceType" => "Observation",
                    "identifier" => [
                        [
                            "system" =>
                                "http://sys-ids.kemkes.go.id/observation/{{Org_id}}",
                            "value" => "O11111A",
                        ],
                    ],
                    "basedOn" => [
                        ["reference" => "urn:uuid:{{ServiceRequest_Lab}}"],
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
                                "code" => "2093-3",
                                "display" =>
                                    "Cholesterol [Mass/volume] in Serum or Plasma",
                            ],
                        ],
                    ],
                    "subject" => ["reference" => "Patient/{{Patient_Id}}"],
                    "encounter" => ["reference" => "urn:uuid:{{Encounter_id}}"],
                    "effectiveDateTime" => "2022-12-26T22:30:10+00:00",
                    "issued" => "2022-12-26T22:30:10+00:00",
                    "performer" => [
                        ["reference" => "Practitioner/{{Practitioner_Lab_Id}}"],
                        ["reference" => "Organization/{{Org_id}}"],
                    ],
                    "valueQuantity" => [
                        "value" => 240,
                        "unit" => "mg/dL",
                        "system" => "http://unitsofmeasure.org",
                        "code" => "mg/dL",
                    ],
                    "interpretation" => [
                        [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.hl7.org/CodeSystem/v3-ObservationInterpretation",
                                    "code" => "H",
                                    "display" => "High",
                                ],
                            ],
                        ],
                    ],
                    "specimen" => ["reference" => "urn:uuid:{{Specimen_Lab}}"],
                    "referenceRange" => [
                        [
                            "high" => [
                                "value" => 200,
                                "unit" => "mg/dL",
                                "system" => "http://unitsofmeasure.org",
                                "code" => "mg/dL",
                            ],
                            "text" => "Normal",
                        ],
                        [
                            "low" => [
                                "value" => 201,
                                "unit" => "mg/dL",
                                "system" => "http://unitsofmeasure.org",
                                "code" => "mg/dL",
                            ],
                            "high" => [
                                "value" => 239,
                                "unit" => "mg/dL",
                                "system" => "http://unitsofmeasure.org",
                                "code" => "mg/dL",
                            ],
                            "text" => "Borderline high",
                        ],
                        [
                            "low" => [
                                "value" => 240,
                                "unit" => "mg/dL",
                                "system" => "http://unitsofmeasure.org",
                                "code" => "mg/dL",
                            ],
                            "text" => "High",
                        ],
                    ],
                ],
                "request" => ["method" => "POST", "url" => "Observation"],
            ],

            // 10. DiagnosticReport
            [
                "fullUrl" => "urn:uuid:{{DiagnosticReport_Lab}}",
                "resource" => [
                    "resourceType" => "DiagnosticReport",
                    "identifier" => [
                        [
                            "use" => "official",
                            "system" =>
                                "http://sys-ids.kemkes.go.id/diagnostic/{{Org_id}}/lab",
                            "value" => "5234342",
                        ],
                    ],
                    "basedOn" => [
                        ["reference" => "urn:uuid:{{ServiceRequest_Lab}}"],
                    ],
                    "status" => "final",
                    "category" => [
                        [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.hl7.org/CodeSystem/v2-0074",
                                    "code" => "CH",
                                    "display" => "Chemistry",
                                ],
                            ],
                        ],
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://loinc.org",
                                "code" => "2093-3",
                                "display" =>
                                    "Cholesterol [Mass/volume] in Serum or Plasma",
                            ],
                        ],
                    ],
                    "subject" => ["reference" => "Patient/{{Patient_Id}}"],
                    "encounter" => ["reference" => "urn:uuid:{{Encounter_id}}"],
                    "effectiveDateTime" => "2022-12-26T22:30:10+00:00",
                    "issued" => "2022-12-27T03:30:00+00:00",
                    "performer" => [
                        ["reference" => "Practitioner/{{Practitioner_Lab_Id}}"],
                        ["reference" => "Organization/{{Org_id}}"],
                    ],
                    "specimen" => [["reference" => "urn:uuid:{{Specimen_Lab}}"]],
                    "result" => [["reference" => "urn:uuid:{{Observation_Lab}}"]],
                    "conclusionCode" => [
                        [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.hl7.org/CodeSystem/v3-ObservationInterpretation",
                                    "code" => "H",
                                    "display" => "High",
                                ],
                            ],
                        ],
                    ],
                ],
                "request" => ["method" => "POST", "url" => "DiagnosticReport"],
            ],

            // 11. Condition diagnosis (sudah dikerjakan)
            [
                "fullUrl" => "urn:uuid:{{Condition_DiagnosisPrimer}}",
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
                                "code" => "N18.5",
                                "display" => "Chronic kidney disease, stage 5",
                            ],
                        ],
                    ],
                    "subject" => [
                        "reference" => "Patient/{{Patient_Id}}",
                        "display" => "{{Patient_Name}}",
                    ],
                    "encounter" => ["reference" => "urn:uuid:{{Encounter_id}}"],
                    "onsetDateTime" => "2022-12-22T08:00:00+00:00",
                    "recordedDate" => "2022-12-22T08:00:00+00:00",
                    "note" => [["text" => "Pasien mengalami Gagal Ginjal Stage 5"]],
                ],
                "request" => ["method" => "POST", "url" => "Condition"],
            ],

            // 12. Condition diagnosis (sudah dikerjakan)
            [
                "fullUrl" => "urn:uuid:{{Condition_DiagnosisSekunder}}",
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
                                "code" => "D63.8",
                                "display" => "Anemia in chronic kidney disease",
                            ],
                        ],
                    ],
                    "subject" => [
                        "reference" => "Patient/{{Patient_Id}}",
                        "display" => "{{Patient_Name}}",
                    ],
                    "encounter" => ["reference" => "urn:uuid:{{Encounter_id}}"],
                    "onsetDateTime" => "2022-12-22T08:00:00+00:00",
                    "recordedDate" => "2022-12-22T08:00:00+00:00",
                    "note" => [["text" => "Pasien mengalami Anemia"]],
                ],
                "request" => ["method" => "POST", "url" => "Condition"],
            ],

            // 13. Procedure
            [
                "fullUrl" => "urn:uuid:{{Procedure_Hemodialisis}}",
                "resource" => [
                    "resourceType" => "Procedure",
                    "status" => "completed",
                    "category" => [
                        "coding" => [
                            [
                                "system" => "http://snomed.info/sct",
                                "code" => "277132007",
                                "display" => "Therapeutic procedure",
                            ],
                        ],
                        "text" => "Prosedur Terapetik",
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://hl7.org/fhir/sid/icd-9-cm",
                                "code" => "39.95",
                                "display" => "Hemodialysis",
                            ],
                        ],
                    ],
                    "subject" => [
                        "reference" => "Patient/{{Patient_Id}}",
                        "display" => "{{Patient_Name}}",
                    ],
                    "encounter" => [
                        "reference" => "urn:uuid:{{Encounter_id}}",
                        "display" =>
                            "Tindakan Hemodialisis {{Patient_Name}} pada Kamis tanggal 22 Desember 2022",
                    ],
                    "performedPeriod" => [
                        "start" => "2022-12-22T18:00:00+00:00",
                        "end" => "2022-12-22T19:27:00+00:00",
                    ],
                    "performer" => [
                        [
                            "actor" => [
                                "reference" => "Practitioner/{{Practitioner_Id}}",
                                "display" => "{{Practitioner_Name}}",
                            ],
                        ],
                    ],
                    "reasonCode" => [
                        [
                            "coding" => [
                                [
                                    "system" => "http://hl7.org/fhir/sid/icd-10",
                                    "code" => "N18.5",
                                    "display" => "Chronic kidney disease, stage 5",
                                ],
                            ],
                        ],
                    ],
                    "note" => [["text" => "Pasien melakukan Cuci Darah."]],
                ],
                "request" => ["method" => "POST", "url" => "Procedure"],
            ],
            [
                "fullUrl" => "urn:uuid:{{Medication_forRequest1}}",
                "resource" => [
                    "resourceType" => "Medication",
                    "meta" => [
                        "profile" => [
                            "https://fhir.kemkes.go.id/r4/StructureDefinition/Medication",
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
                    "identifier" => [
                        [
                            "use" => "official",
                            "system" =>
                                "http://sys-ids.kemkes.go.id/medication/{{Org_id}}",
                            "value" => "123456789-D",
                        ],
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://sys-ids.kemkes.go.id/kfa",
                                "code" => "93000374",
                                "display" =>
                                    "Ringer Lactate (NATURA LABORATORIA PRIMA, 500 mL)",
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
                                "code" => "BS035",
                                "display" => "Infus",
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
                                        "code" => "91000568",
                                        "display" =>
                                            "Calcium Chloride, Dihydration",
                                    ],
                                ],
                            ],
                            "isActive" => true,
                            "strength" => [
                                "numerator" => [
                                    "value" => 0.1,
                                    "system" => "http://unitsofmeasure.org",
                                    "code" => "g",
                                ],
                                "denominator" => [
                                    "value" => 1,
                                    "unit" => "Bottle - unit of product usage",
                                    "system" => "http://snomed.info/sct",
                                    "code" => "419672006",
                                ],
                            ],
                        ],
                        [
                            "itemCodeableConcept" => [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://sys-ids.kemkes.go.id/kfa",
                                        "code" => "91000171",
                                        "display" => "Sodium Chloride",
                                    ],
                                ],
                            ],
                            "isActive" => true,
                            "strength" => [
                                "numerator" => [
                                    "value" => 1.55,
                                    "system" => "http://unitsofmeasure.org",
                                    "code" => "g",
                                ],
                                "denominator" => [
                                    "value" => 1,
                                    "unit" => "Bottle - unit of product usage",
                                    "system" => "http://snomed.info/sct",
                                    "code" => "419672006",
                                ],
                            ],
                        ],
                        [
                            "itemCodeableConcept" => [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://sys-ids.kemkes.go.id/kfa",
                                        "code" => "91000265",
                                        "display" => "Sodium Lactate",
                                    ],
                                ],
                            ],
                            "isActive" => true,
                            "strength" => [
                                "numerator" => [
                                    "value" => 3,
                                    "system" => "http://unitsofmeasure.org",
                                    "code" => "g",
                                ],
                                "denominator" => [
                                    "value" => 1,
                                    "unit" => "Bottle - unit of product usage",
                                    "system" => "http://snomed.info/sct",
                                    "code" => "419672006",
                                ],
                            ],
                        ],
                        [
                            "itemCodeableConcept" => [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://sys-ids.kemkes.go.id/kfa",
                                        "code" => "91000198",
                                        "display" => "Potassium Chloride",
                                    ],
                                ],
                            ],
                            "isActive" => true,
                            "strength" => [
                                "numerator" => [
                                    "value" => 0.15,
                                    "system" => "http://unitsofmeasure.org",
                                    "code" => "g",
                                ],
                                "denominator" => [
                                    "value" => 1,
                                    "unit" => "Bottle - unit of product usage",
                                    "system" => "http://snomed.info/sct",
                                    "code" => "419672006",
                                ],
                            ],
                        ],
                    ],
                ],
                "request" => ["method" => "POST", "url" => "Medication"],
            ],
            [
                "fullUrl" => "urn:uuid:{{Medication_forRequest2}}",
                "resource" => [
                    "resourceType" => "Medication",
                    "meta" => [
                        "profile" => [
                            "https://fhir.kemkes.go.id/r4/StructureDefinition/Medication",
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
                    "identifier" => [
                        [
                            "use" => "official",
                            "system" =>
                                "http://sys-ids.kemkes.go.id/medication/{{Org_id}}",
                            "value" => "123456789-E",
                        ],
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://sys-ids.kemkes.go.id/kfa",
                                "code" => "93000374",
                                "display" =>
                                    "Ringer Lactate (NATURA LABORATORIA PRIMA, 500 mL)",
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
                                "code" => "BS035",
                                "display" => "Infus",
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
                                        "code" => "91000568",
                                        "display" =>
                                            "Calcium Chloride, Dihydration",
                                    ],
                                ],
                            ],
                            "isActive" => true,
                            "strength" => [
                                "numerator" => [
                                    "value" => 0.1,
                                    "system" => "http://unitsofmeasure.org",
                                    "code" => "g",
                                ],
                                "denominator" => [
                                    "value" => 1,
                                    "unit" => "Bottle - unit of product usage",
                                    "system" => "http://snomed.info/sct",
                                    "code" => "419672006",
                                ],
                            ],
                        ],
                        [
                            "itemCodeableConcept" => [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://sys-ids.kemkes.go.id/kfa",
                                        "code" => "91000171",
                                        "display" => "Sodium Chloride",
                                    ],
                                ],
                            ],
                            "isActive" => true,
                            "strength" => [
                                "numerator" => [
                                    "value" => 1.55,
                                    "system" => "http://unitsofmeasure.org",
                                    "code" => "g",
                                ],
                                "denominator" => [
                                    "value" => 1,
                                    "unit" => "Bottle - unit of product usage",
                                    "system" => "http://snomed.info/sct",
                                    "code" => "419672006",
                                ],
                            ],
                        ],
                        [
                            "itemCodeableConcept" => [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://sys-ids.kemkes.go.id/kfa",
                                        "code" => "91000265",
                                        "display" => "Sodium Lactate",
                                    ],
                                ],
                            ],
                            "isActive" => true,
                            "strength" => [
                                "numerator" => [
                                    "value" => 3,
                                    "system" => "http://unitsofmeasure.org",
                                    "code" => "g",
                                ],
                                "denominator" => [
                                    "value" => 1,
                                    "unit" => "Bottle - unit of product usage",
                                    "system" => "http://snomed.info/sct",
                                    "code" => "419672006",
                                ],
                            ],
                        ],
                        [
                            "itemCodeableConcept" => [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://sys-ids.kemkes.go.id/kfa",
                                        "code" => "91000198",
                                        "display" => "Potassium Chloride",
                                    ],
                                ],
                            ],
                            "isActive" => true,
                            "strength" => [
                                "numerator" => [
                                    "value" => 0.15,
                                    "system" => "http://unitsofmeasure.org",
                                    "code" => "g",
                                ],
                                "denominator" => [
                                    "value" => 1,
                                    "unit" => "Bottle - unit of product usage",
                                    "system" => "http://snomed.info/sct",
                                    "code" => "419672006",
                                ],
                            ],
                        ],
                    ],
                ],
                "request" => ["method" => "POST", "url" => "Medication"],
            ],
            [
                "fullUrl" => "urn:uuid:{{Medication_forRequest3}}",
                "resource" => [
                    "resourceType" => "Medication",
                    "meta" => [
                        "profile" => [
                            "https://fhir.kemkes.go.id/r4/StructureDefinition/Medication",
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
                    "identifier" => [
                        [
                            "use" => "official",
                            "system" =>
                                "http://sys-ids.kemkes.go.id/medication/{{Org_id}}",
                            "value" => "123456789-F",
                        ],
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://sys-ids.kemkes.go.id/kfa",
                                "code" => "93000374",
                                "display" =>
                                    "Ringer Lactate (NATURA LABORATORIA PRIMA, 500 mL)",
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
                                "code" => "BS035",
                                "display" => "Infus",
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
                                        "code" => "91000568",
                                        "display" =>
                                            "Calcium Chloride, Dihydration",
                                    ],
                                ],
                            ],
                            "isActive" => true,
                            "strength" => [
                                "numerator" => [
                                    "value" => 0.1,
                                    "system" => "http://unitsofmeasure.org",
                                    "code" => "g",
                                ],
                                "denominator" => [
                                    "value" => 1,
                                    "unit" => "Bottle - unit of product usage",
                                    "system" => "http://snomed.info/sct",
                                    "code" => "419672006",
                                ],
                            ],
                        ],
                        [
                            "itemCodeableConcept" => [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://sys-ids.kemkes.go.id/kfa",
                                        "code" => "91000171",
                                        "display" => "Sodium Chloride",
                                    ],
                                ],
                            ],
                            "isActive" => true,
                            "strength" => [
                                "numerator" => [
                                    "value" => 1.55,
                                    "system" => "http://unitsofmeasure.org",
                                    "code" => "g",
                                ],
                                "denominator" => [
                                    "value" => 1,
                                    "unit" => "Bottle - unit of product usage",
                                    "system" => "http://snomed.info/sct",
                                    "code" => "419672006",
                                ],
                            ],
                        ],
                        [
                            "itemCodeableConcept" => [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://sys-ids.kemkes.go.id/kfa",
                                        "code" => "91000265",
                                        "display" => "Sodium Lactate",
                                    ],
                                ],
                            ],
                            "isActive" => true,
                            "strength" => [
                                "numerator" => [
                                    "value" => 3,
                                    "system" => "http://unitsofmeasure.org",
                                    "code" => "g",
                                ],
                                "denominator" => [
                                    "value" => 1,
                                    "unit" => "Bottle - unit of product usage",
                                    "system" => "http://snomed.info/sct",
                                    "code" => "419672006",
                                ],
                            ],
                        ],
                        [
                            "itemCodeableConcept" => [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://sys-ids.kemkes.go.id/kfa",
                                        "code" => "91000198",
                                        "display" => "Potassium Chloride",
                                    ],
                                ],
                            ],
                            "isActive" => true,
                            "strength" => [
                                "numerator" => [
                                    "value" => 0.15,
                                    "system" => "http://unitsofmeasure.org",
                                    "code" => "g",
                                ],
                                "denominator" => [
                                    "value" => 1,
                                    "unit" => "Bottle - unit of product usage",
                                    "system" => "http://snomed.info/sct",
                                    "code" => "419672006",
                                ],
                            ],
                        ],
                    ],
                ],
                "request" => ["method" => "POST", "url" => "Medication"],
            ],
            [
                "fullUrl" => "urn:uuid:{{MedicationRequest_id1}}",
                "resource" => [
                    "resourceType" => "MedicationRequest",
                    "identifier" => [
                        [
                            "use" => "official",
                            "system" =>
                                "http://sys-ids.kemkes.go.id/prescription/{{Org_id}}",
                            "value" => "123456788",
                        ],
                        [
                            "use" => "official",
                            "system" =>
                                "http://sys-ids.kemkes.go.id/prescription-item/{{Org_id}}",
                            "value" => "123456788-2",
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
                                    "code" => "inpatient",
                                    "display" => "Inpatient",
                                ],
                            ],
                        ],
                    ],
                    "priority" => "routine",
                    "medicationReference" => [
                        "reference" => "urn:uuid:{{Medication_forRequest1}}",
                        "display" =>
                            "Ringer Lactate (NATURA LABORATORIA PRIMA, 500 mL)",
                    ],
                    "subject" => [
                        "reference" => "Patient/{{Patient_Id}}",
                        "display" => "{{Patient_Name}}",
                    ],
                    "encounter" => ["reference" => "urn:uuid:{{Encounter_id}}"],
                    "authoredOn" => "2022-12-25T14:00:00+00:00",
                    "requester" => [
                        "reference" => "Practitioner/{{Practitioner_Id}}",
                        "display" => "{{Practitioner_Name}}",
                    ],
                    "reasonReference" => [
                        [
                            "reference" => "urn:uuid:{{Condition_DiagnosisPrimer}}",
                            "display" => "Chronic kidney disease, stage 5",
                        ],
                    ],
                    "dosageInstruction" => [
                        [
                            "sequence" => 1,
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
                            "patientInstruction" => "1 botol per 8 jam",
                            "timing" => [
                                "repeat" => [
                                    "frequency" => 1,
                                    "period" => 8,
                                    "periodUnit" => "h",
                                ],
                            ],
                            "route" => [
                                "coding" => [
                                    [
                                        "system" => "http://www.whocc.no/atc",
                                        "code" => "P",
                                        "display" => "Parenteral",
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
                                        "value" => 1,
                                        "unit" => "Bottle - unit of product usage",
                                        "system" => "http://snomed.info/sct",
                                        "code" => "419672006",
                                    ],
                                ],
                            ],
                        ],
                    ],
                    "dispenseRequest" => [
                        "dispenseInterval" => [
                            "value" => 8,
                            "unit" => "hour",
                            "system" => "http://unitsofmeasure.org",
                            "code" => "h",
                        ],
                        "validityPeriod" => [
                            "start" => "2022-12-25T14:00:00+00:00",
                            "end" => "2023-01-24T14:00:00+00:00",
                        ],
                        "numberOfRepeatsAllowed" => 0,
                        "quantity" => [
                            "value" => 1,
                            "unit" => "Bottle - unit of product usage",
                            "system" => "http://snomed.info/sct",
                            "code" => "419672006",
                        ],
                        "expectedSupplyDuration" => [
                            "value" => 8,
                            "unit" => "hour",
                            "system" => "http://unitsofmeasure.org",
                            "code" => "h",
                        ],
                        "performer" => ["reference" => "Organization/{{Org_id}}"],
                    ],
                ],
                "request" => ["method" => "POST", "url" => "MedicationRequest"],
            ],
            [
                "fullUrl" => "urn:uuid:{{MedicationRequest_id2}}",
                "resource" => [
                    "resourceType" => "MedicationRequest",
                    "identifier" => [
                        [
                            "use" => "official",
                            "system" =>
                                "http://sys-ids.kemkes.go.id/prescription/{{Org_id}}",
                            "value" => "123456788",
                        ],
                        [
                            "use" => "official",
                            "system" =>
                                "http://sys-ids.kemkes.go.id/prescription-item/{{Org_id}}",
                            "value" => "123456788-2",
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
                                    "code" => "inpatient",
                                    "display" => "Inpatient",
                                ],
                            ],
                        ],
                    ],
                    "priority" => "routine",
                    "medicationReference" => [
                        "reference" => "urn:uuid:{{Medication_forRequest2}}",
                        "display" =>
                            "Ringer Lactate (NATURA LABORATORIA PRIMA, 500 mL)",
                    ],
                    "subject" => [
                        "reference" => "Patient/{{Patient_Id}}",
                        "display" => "{{Patient_Name}}",
                    ],
                    "encounter" => ["reference" => "urn:uuid:{{Encounter_id}}"],
                    "authoredOn" => "2022-12-25T22:00:00+00:00",
                    "requester" => [
                        "reference" => "Practitioner/{{Practitioner_Id}}",
                        "display" => "{{Practitioner_Name}}",
                    ],
                    "reasonReference" => [
                        [
                            "reference" => "urn:uuid:{{Condition_DiagnosisPrimer}}",
                            "display" => "Chronic kidney disease, stage 5",
                        ],
                    ],
                    "dosageInstruction" => [
                        [
                            "sequence" => 1,
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
                            "patientInstruction" => "1 botol per 8 jam",
                            "timing" => [
                                "repeat" => [
                                    "frequency" => 1,
                                    "period" => 8,
                                    "periodUnit" => "h",
                                ],
                            ],
                            "route" => [
                                "coding" => [
                                    [
                                        "system" => "http://www.whocc.no/atc",
                                        "code" => "P",
                                        "display" => "Parenteral",
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
                                        "value" => 1,
                                        "unit" => "Bottle - unit of product usage",
                                        "system" => "http://snomed.info/sct",
                                        "code" => "419672006",
                                    ],
                                ],
                            ],
                        ],
                    ],
                    "dispenseRequest" => [
                        "dispenseInterval" => [
                            "value" => 8,
                            "unit" => "hour",
                            "system" => "http://unitsofmeasure.org",
                            "code" => "h",
                        ],
                        "validityPeriod" => [
                            "start" => "2022-12-25T14:00:00+00:00",
                            "end" => "2023-01-24T14:00:00+00:00",
                        ],
                        "numberOfRepeatsAllowed" => 0,
                        "quantity" => [
                            "value" => 1,
                            "unit" => "Bottle - unit of product usage",
                            "system" => "http://snomed.info/sct",
                            "code" => "419672006",
                        ],
                        "expectedSupplyDuration" => [
                            "value" => 8,
                            "unit" => "hour",
                            "system" => "http://unitsofmeasure.org",
                            "code" => "h",
                        ],
                        "performer" => ["reference" => "Organization/{{Org_id}}"],
                    ],
                ],
                "request" => ["method" => "POST", "url" => "MedicationRequest"],
            ],
            [
                "fullUrl" => "urn:uuid:{{MedicationRequest_id3}}",
                "resource" => [
                    "resourceType" => "MedicationRequest",
                    "identifier" => [
                        [
                            "use" => "official",
                            "system" =>
                                "http://sys-ids.kemkes.go.id/prescription/{{Org_id}}",
                            "value" => "123456788",
                        ],
                        [
                            "use" => "official",
                            "system" =>
                                "http://sys-ids.kemkes.go.id/prescription-item/{{Org_id}}",
                            "value" => "123456788-2",
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
                                    "code" => "inpatient",
                                    "display" => "Inpatient",
                                ],
                            ],
                        ],
                    ],
                    "priority" => "routine",
                    "medicationReference" => [
                        "reference" => "urn:uuid:{{Medication_forRequest3}}",
                        "display" =>
                            "Ringer Lactate (NATURA LABORATORIA PRIMA, 500 mL)",
                    ],
                    "subject" => [
                        "reference" => "Patient/{{Patient_Id}}",
                        "display" => "{{Patient_Name}}",
                    ],
                    "encounter" => ["reference" => "urn:uuid:{{Encounter_id}}"],
                    "authoredOn" => "2022-12-26T13:00:00+00:00",
                    "requester" => [
                        "reference" => "Practitioner/{{Practitioner_Id}}",
                        "display" => "{{Practitioner_Name}}",
                    ],
                    "reasonReference" => [
                        [
                            "reference" => "urn:uuid:{{Condition_DiagnosisPrimer}}",
                            "display" => "Chronic kidney disease, stage 5",
                        ],
                    ],
                    "dosageInstruction" => [
                        [
                            "sequence" => 1,
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
                            "patientInstruction" => "1 botol per 8 jam",
                            "timing" => [
                                "repeat" => [
                                    "frequency" => 1,
                                    "period" => 8,
                                    "periodUnit" => "h",
                                ],
                            ],
                            "route" => [
                                "coding" => [
                                    [
                                        "system" => "http://www.whocc.no/atc",
                                        "code" => "P",
                                        "display" => "Parenteral",
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
                                        "value" => 1,
                                        "unit" => "Bottle - unit of product usage",
                                        "system" => "http://snomed.info/sct",
                                        "code" => "419672006",
                                    ],
                                ],
                            ],
                        ],
                    ],
                    "dispenseRequest" => [
                        "dispenseInterval" => [
                            "value" => 8,
                            "unit" => "hour",
                            "system" => "http://unitsofmeasure.org",
                            "code" => "h",
                        ],
                        "validityPeriod" => [
                            "start" => "2022-12-25T14:00:00+00:00",
                            "end" => "2023-01-24T14:00:00+00:00",
                        ],
                        "numberOfRepeatsAllowed" => 0,
                        "quantity" => [
                            "value" => 1,
                            "unit" => "Bottle - unit of product usage",
                            "system" => "http://snomed.info/sct",
                            "code" => "419672006",
                        ],
                        "expectedSupplyDuration" => [
                            "value" => 8,
                            "unit" => "hour",
                            "system" => "http://unitsofmeasure.org",
                            "code" => "h",
                        ],
                        "performer" => ["reference" => "Organization/{{Org_id}}"],
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
                        "reference" => "Patient/{{Patient_Id}}",
                        "display" => "{{Patient_Name}}",
                    ],
                    "encounter" => ["reference" => "urn:uuid:{{Encounter_id}}"],
                    "authored" => "2022-12-26T10:00:00+07:00",
                    "author" => [
                        "reference" => "Practitioner/{{Practitioner_Apoteker_Id}}",
                    ],
                    "source" => ["reference" => "Patient/{{Patient_Id}}"],
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
                                                "code" => "OV000052",
                                                "display" => "Sesuai",
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
                                                        "code" => "OV000052",
                                                        "display" => "Sesuai",
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
                                            "answer" => [["valueBoolean" => false]],
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
                "fullUrl" => "urn:uuid:{{Medication_forDispense1}}",
                "resource" => [
                    "resourceType" => "Medication",
                    "meta" => [
                        "profile" => [
                            "https://fhir.kemkes.go.id/r4/StructureDefinition/Medication",
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
                    "identifier" => [
                        [
                            "use" => "official",
                            "system" =>
                                "http://sys-ids.kemkes.go.id/medication/{{Org_id}}",
                            "value" => "123456790",
                        ],
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://sys-ids.kemkes.go.id/kfa",
                                "code" => "93000374",
                                "display" =>
                                    "Ringer Lactate (NATURA LABORATORIA PRIMA, 500 mL)",
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
                                "code" => "BS035",
                                "display" => "Infus",
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
                                        "code" => "91000568",
                                        "display" =>
                                            "Calcium Chloride, Dihydration",
                                    ],
                                ],
                            ],
                            "isActive" => true,
                            "strength" => [
                                "numerator" => [
                                    "value" => 0.1,
                                    "system" => "http://unitsofmeasure.org",
                                    "code" => "g",
                                ],
                                "denominator" => [
                                    "value" => 1,
                                    "unit" => "Bottle - unit of product usage",
                                    "system" => "http://snomed.info/sct",
                                    "code" => "419672006",
                                ],
                            ],
                        ],
                        [
                            "itemCodeableConcept" => [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://sys-ids.kemkes.go.id/kfa",
                                        "code" => "91000171",
                                        "display" => "Sodium Chloride",
                                    ],
                                ],
                            ],
                            "isActive" => true,
                            "strength" => [
                                "numerator" => [
                                    "value" => 1.55,
                                    "system" => "http://unitsofmeasure.org",
                                    "code" => "g",
                                ],
                                "denominator" => [
                                    "value" => 1,
                                    "unit" => "Bottle - unit of product usage",
                                    "system" => "http://snomed.info/sct",
                                    "code" => "419672006",
                                ],
                            ],
                        ],
                        [
                            "itemCodeableConcept" => [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://sys-ids.kemkes.go.id/kfa",
                                        "code" => "91000265",
                                        "display" => "Sodium Lactate",
                                    ],
                                ],
                            ],
                            "isActive" => true,
                            "strength" => [
                                "numerator" => [
                                    "value" => 3,
                                    "system" => "http://unitsofmeasure.org",
                                    "code" => "g",
                                ],
                                "denominator" => [
                                    "value" => 1,
                                    "unit" => "Bottle - unit of product usage",
                                    "system" => "http://snomed.info/sct",
                                    "code" => "419672006",
                                ],
                            ],
                        ],
                        [
                            "itemCodeableConcept" => [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://sys-ids.kemkes.go.id/kfa",
                                        "code" => "91000198",
                                        "display" => "Potassium Chloride",
                                    ],
                                ],
                            ],
                            "isActive" => true,
                            "strength" => [
                                "numerator" => [
                                    "value" => 0.15,
                                    "system" => "http://unitsofmeasure.org",
                                    "code" => "g",
                                ],
                                "denominator" => [
                                    "value" => 1,
                                    "unit" => "Bottle - unit of product usage",
                                    "system" => "http://snomed.info/sct",
                                    "code" => "419672006",
                                ],
                            ],
                        ],
                    ],
                    "batch" => [
                        "lotNumber" => "1625042A",
                        "expirationDate" => "2025-07-28",
                    ],
                ],
                "request" => ["method" => "POST", "url" => "Medication"],
            ],
            [
                "fullUrl" => "urn:uuid:{{Medication_forDispense2}}",
                "resource" => [
                    "resourceType" => "Medication",
                    "meta" => [
                        "profile" => [
                            "https://fhir.kemkes.go.id/r4/StructureDefinition/Medication",
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
                    "identifier" => [
                        [
                            "use" => "official",
                            "system" =>
                                "http://sys-ids.kemkes.go.id/medication/{{Org_id}}",
                            "value" => "123456790-1",
                        ],
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://sys-ids.kemkes.go.id/kfa",
                                "code" => "93000374",
                                "display" =>
                                    "Ringer Lactate (NATURA LABORATORIA PRIMA, 500 mL)",
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
                                "code" => "BS035",
                                "display" => "Infus",
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
                                        "code" => "91000568",
                                        "display" =>
                                            "Calcium Chloride, Dihydration",
                                    ],
                                ],
                            ],
                            "isActive" => true,
                            "strength" => [
                                "numerator" => [
                                    "value" => 0.1,
                                    "system" => "http://unitsofmeasure.org",
                                    "code" => "g",
                                ],
                                "denominator" => [
                                    "value" => 1,
                                    "unit" => "Bottle - unit of product usage",
                                    "system" => "http://snomed.info/sct",
                                    "code" => "419672006",
                                ],
                            ],
                        ],
                        [
                            "itemCodeableConcept" => [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://sys-ids.kemkes.go.id/kfa",
                                        "code" => "91000171",
                                        "display" => "Sodium Chloride",
                                    ],
                                ],
                            ],
                            "isActive" => true,
                            "strength" => [
                                "numerator" => [
                                    "value" => 1.55,
                                    "system" => "http://unitsofmeasure.org",
                                    "code" => "g",
                                ],
                                "denominator" => [
                                    "value" => 1,
                                    "unit" => "Bottle - unit of product usage",
                                    "system" => "http://snomed.info/sct",
                                    "code" => "419672006",
                                ],
                            ],
                        ],
                        [
                            "itemCodeableConcept" => [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://sys-ids.kemkes.go.id/kfa",
                                        "code" => "91000265",
                                        "display" => "Sodium Lactate",
                                    ],
                                ],
                            ],
                            "isActive" => true,
                            "strength" => [
                                "numerator" => [
                                    "value" => 3,
                                    "system" => "http://unitsofmeasure.org",
                                    "code" => "g",
                                ],
                                "denominator" => [
                                    "value" => 1,
                                    "unit" => "Bottle - unit of product usage",
                                    "system" => "http://snomed.info/sct",
                                    "code" => "419672006",
                                ],
                            ],
                        ],
                        [
                            "itemCodeableConcept" => [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://sys-ids.kemkes.go.id/kfa",
                                        "code" => "91000198",
                                        "display" => "Potassium Chloride",
                                    ],
                                ],
                            ],
                            "isActive" => true,
                            "strength" => [
                                "numerator" => [
                                    "value" => 0.15,
                                    "system" => "http://unitsofmeasure.org",
                                    "code" => "g",
                                ],
                                "denominator" => [
                                    "value" => 1,
                                    "unit" => "Bottle - unit of product usage",
                                    "system" => "http://snomed.info/sct",
                                    "code" => "419672006",
                                ],
                            ],
                        ],
                    ],
                    "batch" => [
                        "lotNumber" => "1625042A",
                        "expirationDate" => "2025-07-28",
                    ],
                ],
                "request" => ["method" => "POST", "url" => "Medication"],
            ],
            [
                "fullUrl" => "urn:uuid:{{Medication_forDispense3}}",
                "resource" => [
                    "resourceType" => "Medication",
                    "meta" => [
                        "profile" => [
                            "https://fhir.kemkes.go.id/r4/StructureDefinition/Medication",
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
                    "identifier" => [
                        [
                            "use" => "official",
                            "system" =>
                                "http://sys-ids.kemkes.go.id/medication/{{Org_id}}",
                            "value" => "123456790-2",
                        ],
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://sys-ids.kemkes.go.id/kfa",
                                "code" => "93000374",
                                "display" =>
                                    "Ringer Lactate (NATURA LABORATORIA PRIMA, 500 mL)",
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
                                "code" => "BS035",
                                "display" => "Infus",
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
                                        "code" => "91000568",
                                        "display" =>
                                            "Calcium Chloride, Dihydration",
                                    ],
                                ],
                            ],
                            "isActive" => true,
                            "strength" => [
                                "numerator" => [
                                    "value" => 0.1,
                                    "system" => "http://unitsofmeasure.org",
                                    "code" => "g",
                                ],
                                "denominator" => [
                                    "value" => 1,
                                    "unit" => "Bottle - unit of product usage",
                                    "system" => "http://snomed.info/sct",
                                    "code" => "419672006",
                                ],
                            ],
                        ],
                        [
                            "itemCodeableConcept" => [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://sys-ids.kemkes.go.id/kfa",
                                        "code" => "91000171",
                                        "display" => "Sodium Chloride",
                                    ],
                                ],
                            ],
                            "isActive" => true,
                            "strength" => [
                                "numerator" => [
                                    "value" => 1.55,
                                    "system" => "http://unitsofmeasure.org",
                                    "code" => "g",
                                ],
                                "denominator" => [
                                    "value" => 1,
                                    "unit" => "Bottle - unit of product usage",
                                    "system" => "http://snomed.info/sct",
                                    "code" => "419672006",
                                ],
                            ],
                        ],
                        [
                            "itemCodeableConcept" => [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://sys-ids.kemkes.go.id/kfa",
                                        "code" => "91000265",
                                        "display" => "Sodium Lactate",
                                    ],
                                ],
                            ],
                            "isActive" => true,
                            "strength" => [
                                "numerator" => [
                                    "value" => 3,
                                    "system" => "http://unitsofmeasure.org",
                                    "code" => "g",
                                ],
                                "denominator" => [
                                    "value" => 1,
                                    "unit" => "Bottle - unit of product usage",
                                    "system" => "http://snomed.info/sct",
                                    "code" => "419672006",
                                ],
                            ],
                        ],
                        [
                            "itemCodeableConcept" => [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://sys-ids.kemkes.go.id/kfa",
                                        "code" => "91000198",
                                        "display" => "Potassium Chloride",
                                    ],
                                ],
                            ],
                            "isActive" => true,
                            "strength" => [
                                "numerator" => [
                                    "value" => 0.15,
                                    "system" => "http://unitsofmeasure.org",
                                    "code" => "g",
                                ],
                                "denominator" => [
                                    "value" => 1,
                                    "unit" => "Bottle - unit of product usage",
                                    "system" => "http://snomed.info/sct",
                                    "code" => "419672006",
                                ],
                            ],
                        ],
                    ],
                    "batch" => [
                        "lotNumber" => "1625042A",
                        "expirationDate" => "2025-07-28",
                    ],
                ],
                "request" => ["method" => "POST", "url" => "Medication"],
            ],
            [
                "fullUrl" => "urn:uuid:{{MedicationDispense_id1}}",
                "resource" => [
                    "resourceType" => "MedicationDispense",
                    "identifier" => [
                        [
                            "use" => "official",
                            "system" =>
                                "http://sys-ids.kemkes.go.id/prescription/{{Org_id}}",
                            "value" => "123456789",
                        ],
                        [
                            "use" => "official",
                            "system" =>
                                "http://sys-ids.kemkes.go.id/prescription-item/{{Org_id}}",
                            "value" => "123456788-3",
                        ],
                    ],
                    "status" => "completed",
                    "category" => [
                        "coding" => [
                            [
                                "system" =>
                                    "http://terminology.hl7.org/fhir/CodeSystem/medicationdispense-category",
                                "code" => "inpatient",
                                "display" => "Inpatient",
                            ],
                        ],
                    ],
                    "medicationReference" => [
                        "reference" => "urn:uuid:{{Medication_forDispense1}}",
                        "display" =>
                            "Ringer Lactate (NATURA LABORATORIA PRIMA, 500 mL)",
                    ],
                    "subject" => [
                        "reference" => "Patient/{{Patient_Id}}",
                        "display" => "{{Patient_Name}}",
                    ],
                    "context" => ["reference" => "urn:uuid:{{Encounter_id}}"],
                    "performer" => [
                        [
                            "actor" => [
                                "reference" =>
                                    "Practitioner/{{Practitioner_Apoteker_Id}}",
                            ],
                        ],
                    ],
                    "location" => [
                        "reference" => "Location/{{Location_Ruang210_Bed2_id}}",
                        "display" =>
                            "Bed 2, Ruang 210, Bangsal Rawat Inap Kelas 1, Layanan Penyakit Dalam, Lantai 2, Gedung Utama",
                    ],
                    "authorizingPrescription" => [
                        ["reference" => "urn:uuid:{{MedicationRequest_id1}}"],
                    ],
                    "quantity" => [
                        "value" => 1,
                        "unit" => "Bottle - unit of product usage",
                        "system" => "http://snomed.info/sct",
                        "code" => "419672006",
                    ],
                    "daysSupply" => [
                        "value" => 8,
                        "unit" => "hour",
                        "system" => "http://unitsofmeasure.org",
                        "code" => "h",
                    ],
                    "whenPrepared" => "2022-12-25T14:00:00+00:00",
                    "whenHandedOver" => "2022-12-25T14:30:00+00:00",
                    "dosageInstruction" => [
                        [
                            "sequence" => 1,
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
                            "patientInstruction" => "1 botol per 8 jam",
                            "timing" => [
                                "repeat" => [
                                    "frequency" => 1,
                                    "period" => 8,
                                    "periodUnit" => "h",
                                ],
                            ],
                            "route" => [
                                "coding" => [
                                    [
                                        "system" => "http://www.whocc.no/atc",
                                        "code" => "P",
                                        "display" => "Parenteral",
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
                                        "value" => 500,
                                        "unit" => "mL",
                                        "system" => "http://unitsofmeasure.org",
                                        "code" => "mL",
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                "request" => ["method" => "POST", "url" => "MedicationDispense"],
            ],
            [
                "fullUrl" => "urn:uuid:{{MedicationDispense_id2}}",
                "resource" => [
                    "resourceType" => "MedicationDispense",
                    "identifier" => [
                        [
                            "use" => "official",
                            "system" =>
                                "http://sys-ids.kemkes.go.id/prescription/{{Org_id}}",
                            "value" => "123456789",
                        ],
                        [
                            "use" => "official",
                            "system" =>
                                "http://sys-ids.kemkes.go.id/prescription-item/{{Org_id}}",
                            "value" => "123456788-3",
                        ],
                    ],
                    "status" => "completed",
                    "category" => [
                        "coding" => [
                            [
                                "system" =>
                                    "http://terminology.hl7.org/fhir/CodeSystem/medicationdispense-category",
                                "code" => "inpatient",
                                "display" => "Inpatient",
                            ],
                        ],
                    ],
                    "medicationReference" => [
                        "reference" => "urn:uuid:{{Medication_forDispense2}}",
                        "display" =>
                            "Ringer Lactate (NATURA LABORATORIA PRIMA, 500 mL)",
                    ],
                    "subject" => [
                        "reference" => "Patient/{{Patient_Id}}",
                        "display" => "{{Patient_Name}}",
                    ],
                    "context" => ["reference" => "urn:uuid:{{Encounter_id}}"],
                    "performer" => [
                        [
                            "actor" => [
                                "reference" =>
                                    "Practitioner/{{Practitioner_Apoteker_Id}}",
                            ],
                        ],
                    ],
                    "location" => [
                        "reference" => "Location/{{Location_Ruang210_Bed2_id}}",
                        "display" =>
                            "Bed 2, Ruang 210, Bangsal Rawat Inap Kelas 1, Layanan Penyakit Dalam, Lantai 2, Gedung Utama",
                    ],
                    "authorizingPrescription" => [
                        ["reference" => "urn:uuid:{{MedicationRequest_id2}}"],
                    ],
                    "quantity" => [
                        "value" => 1,
                        "unit" => "Bottle - unit of product usage",
                        "system" => "http://snomed.info/sct",
                        "code" => "419672006",
                    ],
                    "daysSupply" => [
                        "value" => 8,
                        "unit" => "hour",
                        "system" => "http://unitsofmeasure.org",
                        "code" => "h",
                    ],
                    "whenPrepared" => "2022-12-25T22:00:00+00:00",
                    "whenHandedOver" => "2022-12-25T22:00:00+00:00",
                    "dosageInstruction" => [
                        [
                            "sequence" => 1,
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
                            "patientInstruction" => "1 botol per 8 jam",
                            "timing" => [
                                "repeat" => [
                                    "frequency" => 1,
                                    "period" => 8,
                                    "periodUnit" => "h",
                                ],
                            ],
                            "route" => [
                                "coding" => [
                                    [
                                        "system" => "http://www.whocc.no/atc",
                                        "code" => "P",
                                        "display" => "Parenteral",
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
                                        "value" => 500,
                                        "unit" => "mL",
                                        "system" => "http://unitsofmeasure.org",
                                        "code" => "mL",
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                "request" => ["method" => "POST", "url" => "MedicationDispense"],
            ],
            [
                "fullUrl" => "urn:uuid:{{MedicationDispense_id3}}",
                "resource" => [
                    "resourceType" => "MedicationDispense",
                    "identifier" => [
                        [
                            "use" => "official",
                            "system" =>
                                "http://sys-ids.kemkes.go.id/prescription/{{Org_id}}",
                            "value" => "123456789",
                        ],
                        [
                            "use" => "official",
                            "system" =>
                                "http://sys-ids.kemkes.go.id/prescription-item/{{Org_id}}",
                            "value" => "123456788-3",
                        ],
                    ],
                    "status" => "completed",
                    "category" => [
                        "coding" => [
                            [
                                "system" =>
                                    "http://terminology.hl7.org/fhir/CodeSystem/medicationdispense-category",
                                "code" => "inpatient",
                                "display" => "Inpatient",
                            ],
                        ],
                    ],
                    "medicationReference" => [
                        "reference" => "urn:uuid:{{Medication_forDispense3}}",
                        "display" =>
                            "Ringer Lactate (NATURA LABORATORIA PRIMA, 500 mL)",
                    ],
                    "subject" => [
                        "reference" => "Patient/{{Patient_Id}}",
                        "display" => "{{Patient_Name}}",
                    ],
                    "context" => ["reference" => "urn:uuid:{{Encounter_id}}"],
                    "performer" => [
                        [
                            "actor" => [
                                "reference" =>
                                    "Practitioner/{{Practitioner_Apoteker_Id}}",
                            ],
                        ],
                    ],
                    "location" => [
                        "reference" => "Location/{{Location_Ruang210_Bed2_id}}",
                        "display" =>
                            "Bed 2, Ruang 210, Bangsal Rawat Inap Kelas 1, Layanan Penyakit Dalam, Lantai 2, Gedung Utama",
                    ],
                    "authorizingPrescription" => [
                        ["reference" => "urn:uuid:{{MedicationRequest_id3}}"],
                    ],
                    "quantity" => [
                        "value" => 1,
                        "unit" => "Bottle - unit of product usage",
                        "system" => "http://snomed.info/sct",
                        "code" => "419672006",
                    ],
                    "daysSupply" => [
                        "value" => 8,
                        "unit" => "hour",
                        "system" => "http://unitsofmeasure.org",
                        "code" => "h",
                    ],
                    "whenPrepared" => "2022-12-26T13:00:00+00:00",
                    "whenHandedOver" => "2022-12-26T13:00:00+00:00",
                    "dosageInstruction" => [
                        [
                            "sequence" => 1,
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
                            "patientInstruction" => "1 botol per 8 jam",
                            "timing" => [
                                "repeat" => [
                                    "frequency" => 1,
                                    "period" => 8,
                                    "periodUnit" => "h",
                                ],
                            ],
                            "route" => [
                                "coding" => [
                                    [
                                        "system" => "http://www.whocc.no/atc",
                                        "code" => "P",
                                        "display" => "Parenteral",
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
                                        "value" => 500,
                                        "unit" => "mL",
                                        "system" => "http://unitsofmeasure.org",
                                        "code" => "mL",
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                "request" => ["method" => "POST", "url" => "MedicationDispense"],
            ],
            [
                "fullUrl" => "urn:uuid:{{Procedure_Edukasi}}",
                "resource" => [
                    "resourceType" => "Procedure",
                    "status" => "completed",
                    "category" => [
                        "coding" => [
                            [
                                "system" => "http://snomed.info/sct",
                                "code" => "409073007",
                                "display" => "Education",
                            ],
                        ],
                        "text" => "Education",
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://snomed.info/sct",
                                "code" => "84635008",
                                "display" =>
                                    "Disease process or condition education ",
                            ],
                        ],
                    ],
                    "subject" => [
                        "reference" => "Patient/{{Patient_Id}}",
                        "display" => "{{Patient_Name}}",
                    ],
                    "encounter" => [
                        "reference" => "urn:uuid:{{Encounter_id}}",
                        "display" =>
                            "Edukasi Proses Penyakit, Diagnosis, dan Rencana Asuhan kepada {{Patient_Name}} di hari Kamis, 22 Desember 2022",
                    ],
                    "performedPeriod" => [
                        "start" => "2022-12-22T10:00:00+00:00",
                        "end" => "2022-12-22T11:00:00+00:00",
                    ],
                    "performer" => [
                        [
                            "actor" => [
                                "reference" => "Practitioner/{{Practitioner_Id}}",
                            ],
                        ],
                    ],
                    "note" => [
                        [
                            "text" =>
                                "Edukasi Proses Penyakit, Diagnosis, dan Rencana Asuhan",
                        ],
                    ],
                ],
                "request" => ["method" => "POST", "url" => "Procedure"],
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
                    "subject" => ["reference" => "Patient/{{Patient_Id}}"],
                    "encounter" => [
                        "reference" => "urn:uuid:{{Encounter_id}}",
                        "display" =>
                            "Pemeriksaan Kriteria untuk Rencana Pemulangan {{Patient_Name}} di hari Kamis, 25 Desember 2022",
                    ],
                    "effectiveDateTime" => "2022-12-25T08:00:00+00:00",
                    "issued" => "2022-12-25T08:00:00+00:00",
                    "performer" => [
                        ["reference" => "Practitioner/{{Practitioner_Id}}"],
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
                    "status" => "active",
                    "intent" => "plan",
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
                    "title" => "Perencanaan Pemulangan Pasien",
                    "description" =>
                        "Pasien akan melakukan perawatan berkelanjutan atau panjang. Rutin melakukan Cuci Darah setiap 1 kali 1 Bulan",
                    "subject" => [
                        "reference" => "Patient/{{Patient_Id}}",
                        "display" => "{{Patient_Name}}",
                    ],
                    "encounter" => ["reference" => "urn:uuid:{{Encounter_id}}"],
                    "created" => "2022-12-29T16:00:00+00:00",
                    "author" => ["reference" => "Practitioner/{{Practitioner_Id}}"],
                ],
                "request" => ["method" => "POST", "url" => "CarePlan"],
            ],
            [
                "fullUrl" => "urn:uuid:{{ClinicalImpression_Prognosis}}",
                "resource" => [
                    "resourceType" => "ClinicalImpression",
                    "identifier" => [
                        [
                            "use" => "official",
                            "system" =>
                                "http://sys-ids.kemkes.go.id/clinicalimpression/{{Org_id}}",
                            "value" => "Prognosis_0001234",
                        ],
                    ],
                    "status" => "completed",
                    "description" =>
                        "Ibu {{Patient_Name}} Terdiagnosis Gagal Ginjal",
                    "subject" => [
                        "reference" => "Patient/{{Patient_Id}}",
                        "display" => "{{Patient_Name}}",
                    ],
                    "encounter" => [
                        "reference" => "urn:uuid:{{Encounter_id}}",
                        "display" =>
                            "Kunjungan {{Patient_Name}} di hari Kamis, 22 Desember 2022",
                    ],
                    "effectiveDateTime" => "2022-12-22T10:00:00+00:00",
                    "date" => "2022-12-22T10:00:00+00:00",
                    "assessor" => [
                        "reference" => "Practitioner/{{Practitioner_Id}}",
                    ],
                    "problem" => [
                        ["reference" => "urn:uuid:{{Condition_DiagnosisPrimer}}"],
                    ],
                    "summary" => "Prognosis Gagal Ginjal Stage 5",
                    "finding" => [
                        [
                            "itemCodeableConcept" => [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://hl7.org/fhir/sid/icd-10",
                                        "code" => "N18.5",
                                        "display" =>
                                            "Chronic kidney disease, stage 5",
                                    ],
                                ],
                            ],
                            "itemReference" => [
                                "reference" =>
                                    "urn:uuid:{{Condition_DiagnosisPrimer}}",
                            ],
                        ],
                    ],
                    "prognosisCodeableConcept" => [
                        [
                            "coding" => [
                                [
                                    "system" => "http://snomed.info/sct",
                                    "code" => "65872000",
                                    "display" => "Fair prognosis",
                                ],
                            ],
                        ],
                    ],
                ],
                "request" => ["method" => "POST", "url" => "ClinicalImpression"],
            ],
            [
                "fullUrl" => "urn:uuid:{{ServiceRequest_Kontrol}}",
                "resource" => [
                    "resourceType" => "ServiceRequest",
                    "identifier" => [
                        [
                            "system" =>
                                "http://sys-ids.kemkes.go.id/servicerequest/{{Org_id}}",
                            "value" => "00001",
                        ],
                    ],
                    "status" => "active",
                    "intent" => "original-order",
                    "category" => [
                        [
                            "coding" => [
                                [
                                    "system" => "http://snomed.info/sct",
                                    "code" => "3457005",
                                    "display" => "Patient referral",
                                ],
                            ],
                        ],
                    ],
                    "priority" => "routine",
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://snomed.info/sct",
                                "code" => "185389009",
                                "display" => "Follow-up visit",
                            ],
                        ],
                        "text" => "Kontrol 1 minggu Pasca Rawat Inap",
                    ],
                    "subject" => ["reference" => "Patient/{{Patient_Id}}"],
                    "encounter" => [
                        "reference" => "urn:uuid:{{Encounter_id}}",
                        "display" =>
                            "Kunjungan {{Patient_Name}} di hari Kamis, 22 Desember 2022",
                    ],
                    "occurrenceDateTime" => "2022-12-26T09:30:00+00:00",
                    "authoredOn" => "2022-12-26T09:30:00+00:00",
                    "requester" => [
                        "reference" => "Practitioner/{{Practitioner_Id}}",
                        "display" => "{{Practitioner_Name}}",
                    ],
                    "performer" => [
                        [
                            "reference" => "Practitioner/{{Practitioner_Id}}",
                            "display" => "Fatma",
                        ],
                    ],
                    "locationReference" => [
                        [
                            "reference" =>
                                "Location/308680cc-4007-46c3-9818-21d3d6340467",
                            "display" =>
                                "Poli Penyakit Dalam, Divisi Pelayanan Medik",
                        ],
                    ],
                    "reasonCode" => [
                        [
                            "coding" => [
                                [
                                    "system" => "http://hl7.org/fhir/sid/icd-10",
                                    "code" => "N18.5",
                                    "display" => "Chronic kidney disease, stage 5",
                                ],
                            ],
                            "text" => "Kontrol rutin 1 minggu pertama",
                        ],
                    ],
                    "patientInstruction" =>
                        "Kontrol rutin 1 minggu pasca Rawat Inap. Dalam keadaan darurat dapat menghubungi hotline Fasyankes di nomor 14045",
                ],
                "request" => ["method" => "POST", "url" => "ServiceRequest"],
            ],
            [
                "fullUrl" => "urn:uuid:{{Condition_Stabil}}",
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
                                    "code" => "problem-list-item",
                                    "display" => "Problem List Item",
                                ],
                            ],
                        ],
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://snomed.info/sct",
                                "code" => "359746009",
                                "display" => "Patient\'s condition stable",
                            ],
                        ],
                    ],
                    "subject" => [
                        "reference" => "Patient/{{Patient_Id}}",
                        "display" => "{{Patient_Name}}",
                    ],
                    "encounter" => [
                        "reference" => "urn:uuid:{{Encounter_id}}",
                        "display" =>
                            "Kunjungan Rawat Inap {{Patient_Name}} di hari Kamis, 22 Desember 2022",
                    ],
                ],
                "request" => ["method" => "POST", "url" => "Condition"],
            ],
        ],
    ];
    }
}
