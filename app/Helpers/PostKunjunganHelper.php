<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Str;

class PostKunjunganHelper
{
    public static function generateUuid()
    {
        return (string) Str::orderedUuid();
    }

    public static function form($request)
    {

        $encounter = self::generateUuid();

        $practitioner = $request->datasimpeg['satset_uuid'];

        $taskid = collect($request->taskid);
        if (count($taskid) === 0) {
            return response()->json([
                'message' => 'Maaf ... Pasien Ini Tidak Mempunyai TASK ID'
            ], 500);
        }

        //   // 1 (mulai waktu tunggu admisi),
        //   // 2 (akhir waktu tunggu admisi/mulai waktu layan admisi),
        //   // 3 (akhir waktu layan admisi/mulai waktu tunggu poli),
        //   // 4 (akhir waktu tunggu poli/mulai waktu layan poli),
        //   // 5 (akhir waktu layan poli/mulai waktu tunggu farmasi),
        //   // 6 (akhir waktu tunggu farmasi/mulai waktu layan farmasi membuat obat),
        //   // 7 (akhir waktu obat selesai dibuat),
        //   // 99 (tidak hadir/batal)

        // $task3 = Carbon::parse($taskid[0]['created_at'])->locale('id');
        $task3 = $taskid->filter(function ($item) {
            return $item['taskid'] === '3';
        })->first();
        $task4 = $taskid->filter(function ($item) {
            return $item['taskid'] === '4';
        })->first();
        $task5 = $taskid->filter(function ($item) {
            return $item['taskid'] === '5';
        })->first();

        if (!$task3 || !$task4 || !$task5) {
            return response()->json([
                'message' => 'Maaf ... TASK iD Tdk lengkap'
            ], 500);
        }

        // return Carbon::parse($task3['created_at']);

        $antri = Carbon::parse($task3['created_at'])->toIso8601String();
        $start = Carbon::parse($task4['created_at'])->toIso8601String();
        $end = Carbon::parse($task5['created_at'])->toIso8601String();

        setlocale(LC_ALL, 'IND');
        $dt = Carbon::parse($request->tgl_kunjungan)->locale('id');
        $dt->settings(['formatFunction' => 'translatedFormat']);
        $tgl_kunjungan = $dt->format('l, j F Y');
        // $tgl_kunjungan = $dt->format('l, j F Y ; h:i a');

        $rajal_org = '4b8fb632-6435-4fc1-8ea0-7aacc39974d6';
        $organization_id = BridgingSatsetHelper::organization_id();

        // DIAGNOSA

        $diagnosa = [];
        foreach ($request->diagnosa as $key => $value) {
            $uuid = self::generateUuid();
            $data = [
                "condition" => [
                    "reference" => "urn:uuid:$uuid",
                    "display" => $value['masterdiagnosa']['rs4']
                ],
                "use" => [
                    "coding" => [
                        [
                            "system" => "http://terminology.hl7.org/CodeSystem/diagnosis-role",
                            "code" => "DD",
                            "display" => "Discharge diagnosis"
                        ]
                    ]
                ],
                "rank" => $key + 1
            ];

            $diagnosa[] = $data;
        }


        // return $antri;
        #Bundle #1
        $body =
            [
                "resourceType" => "Bundle",
                "type" => "transaction",
                "entry" => [
                    // ENCOUNTER
                    [
                        "fullUrl" => "urn:uuid:$encounter",
                        "resource" => [
                            "resourceType" => "Encounter",
                            "status" => "finished",
                            "class" => [
                                "system" => "http://terminology.hl7.org/CodeSystem/v3-ActCode",
                                "code" => "AMB",
                                "display" => "ambulatory"
                            ],
                            "subject" => [
                                "reference" => "Patient/$request->pasien_uuid",
                                "display" => $request->nama
                            ],
                            "participant" => [
                                [
                                    "type" => [
                                        [
                                            "coding" => [
                                                [
                                                    "system" => "http://terminology.hl7.org/CodeSystem/v3-ParticipationType",
                                                    "code" => "ATND",
                                                    "display" => "attender"
                                                ]
                                            ]
                                        ]
                                    ],
                                    "individual" => [
                                        "reference" => "Practitioner/$practitioner",
                                        "display" => $request->datasimpeg['nama']
                                    ]
                                ]
                            ],
                            "period" => [
                                "start" => $antri,
                                "end" => $end
                            ],
                            "location" => [
                                [
                                    "location" => [
                                        "reference" => "Location/" . $request['relmpoli']['ruang']['satset_uuid'],
                                        "display" => "Ruang " . $request['relmpoli']['ruang']['ruang'] . " " . $request['relmpoli']['panggil_antrian'] . ", RSUD Mohamad Saleh, Lantai " . $request['relmpoli']['ruang']['lantai'] . ", Gedung " . $request['relmpoli']['ruang']['gedung']
                                        // "display" => $request['relmpoli']['ruang']['gedung']
                                    ]
                                ]
                            ],
                            // "diagnosis" => [
                            //     [
                            //         "condition" => [
                            //             "reference" => "urn:uuid:ba5a7dec-023f-45e1-adb9-1b9d71737a5f",
                            //             "display" => "Acute appendicitis, other and unspecified"
                            //         ],
                            //         "use" => [
                            //             "coding" => [
                            //                 [
                            //                     "system" => "http://terminology.hl7.org/CodeSystem/diagnosis-role",
                            //                     "code" => "DD",
                            //                     "display" => "Discharge diagnosis"
                            //                 ]
                            //             ]
                            //         ],
                            //         "rank" => 1
                            //     ],
                            //     [
                            //         "condition" => [
                            //             "reference" => "urn:uuid:470fc62d-9ab1-4c90-8d24-fb245c105c59",
                            //             "display" => "Dengue haemorrhagic fever"
                            //         ],
                            //         "use" => [
                            //             "coding" => [
                            //                 [
                            //                     "system" => "http://terminology.hl7.org/CodeSystem/diagnosis-role",
                            //                     "code" => "DD",
                            //                     "display" => "Discharge diagnosis"
                            //                 ]
                            //             ]
                            //         ],
                            //         "rank" => 2
                            //     ]
                            // ],

                            "diagnosis" => $diagnosa,
                            "statusHistory" => [
                                [
                                    "status" => "arrived",
                                    "period" => [
                                        "start" => $antri,
                                        "end" => $start
                                    ]
                                ],
                                [
                                    "status" => "in-progress",
                                    "period" => [
                                        "start" => $start,
                                        "end" => $end
                                    ]
                                ],
                                [
                                    "status" => "finished",
                                    "period" => [
                                        "start" => $end,
                                        "end" => $end
                                    ]
                                ]
                            ],
                            "serviceProvider" => [
                                // "reference" => "Organization/$organization_id"
                                "reference" => "Organization/$organization_id"
                            ],

                            // gak yakin
                            "identifier" => [
                                [
                                    "system" => "http://sys-ids.kemkes.go.id/encounter/$organization_id",
                                    "value" => $request->pasien_uuid
                                    // "value" => "P20240001"
                                ]
                            ]
                        ],
                        "request" => [
                            "method" => "POST",
                            "url" => "Encounter"
                        ]
                    ],
                ]
            ];



        //  CONDITION
        foreach ($request->diagnosa as $key => $value) {
            $cond =
                [
                    // "fullUrl" => "urn:uuid:ba5a7dec-023f-45e1-adb9-1b9d71737a5f",
                    "fullUrl" => $diagnosa[$key]['condition']['reference'],
                    "resource" => [
                        "resourceType" => "Condition",
                        "clinicalStatus" => [
                            "coding" => [
                                [
                                    "system" => "http://terminology.hl7.org/CodeSystem/condition-clinical",
                                    "code" => "active",
                                    "display" => "Active"
                                ]
                            ]
                        ],
                        "category" => [
                            [
                                "coding" => [
                                    [
                                        "system" => "http://terminology.hl7.org/CodeSystem/condition-category",
                                        "code" => "encounter-diagnosis",
                                        "display" => "Encounter Diagnosis"
                                    ]
                                ]
                            ]
                        ],
                        "code" => [
                            "coding" => [
                                [
                                    "system" => "http://hl7.org/fhir/sid/icd-10",
                                    "code" => $value['rs3'],
                                    "display" => $value['masterdiagnosa']['rs4']
                                ]
                            ]
                        ],
                        "subject" => [
                            "reference" => "Patient/$request->pasien_uuid",
                            "display" => $request->nama
                        ],
                        "encounter" => [
                            "reference" => "urn:uuid:$encounter",
                            "display" => "Kunjungan $request->nama di hari $tgl_kunjungan"
                        ]
                    ],
                    "request" => [
                        "method" => "POST",
                        "url" => "Condition"
                    ]
                ];

            array_push($body['entry'], $cond);
        }


        return $body;
    }
}
