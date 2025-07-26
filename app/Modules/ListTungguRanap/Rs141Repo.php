<?php

declare(strict_types=1);

namespace App\Modules\ListTungguRanap;

use Illuminate\Support\Facades\DB;
use Exception;

class Rs141Repo
{

    protected $tableName = "rs141";
    protected $selectableColumns = [
        "rs141.rs1 as noreg",
        "rs141.rs2 as norm",
        // "rs141.created_at",
        // "rs141.updated_at",
        // "GROUP_CONCAT(JSON_OBJECT(
        //     'id', courses.id,
        //     'name', courses.name,
        //     'created_at', courses.created_at,
        //     'updated_at', courses.updated_at
        // ) SEPARATOR '|+|') AS 'courses'"
    ];
    protected $joins = [
        "LEFT JOIN rs23 ON rs23.rs2 = rs141.rs2"
        // "LEFT JOIN courses ON courses.id = rs141_courses_enrollment.courses_id"
    ];

    public function getAll()
    {
        $selectColumns = implode(", ", $this->selectableColumns);
        $joins = implode(" ", $this->joins);
        $result = json_decode(json_encode(DB::select("SELECT $selectColumns FROM {$this->tableName} $joins GROUP BY rs141.rs1 LIMIT 0,10")), true);
        // return array_map(function ($query) {
        //     if ($query["courses"] !== '{"id": null, "name": null, "created_at": null, "updated_at": null}') {
        //         $query["courses"] = array_map(function ($row) {
        //             return json_decode($row, true);
        //         }, explode("|+|", $query["courses"]));
        //     } else {
        //         $query["courses"] = [];
        //     }
        //     return $query;
        // }, $result);

        return $result;
    }
    // public function get(int $id): Students
    // {
    //     $selectColumns = implode(", ", $this->selectableColumns);
    //     $joins = implode(" ", $this->joins);
    //     $result = json_decode(json_encode(DB::selectOne("SELECT $selectColumns
    //         FROM {$this->tableName}
    //         $joins
    //         WHERE {$this->tableName}.id = :id
    //         GROUP BY students.id", [
    //         "id" => $id
    //     ])), true);
    //     if ($result === null) {
    //         throw new Exception("Invalid Student Id");
    //     }

    //     if ($result["courses"] !== '{"id": null, "name": null, "created_at": null, "updated_at": null}') {
    //         $result["courses"] = array_map(function ($row) {
    //             return json_decode($row, true);
    //         }, explode("|+|", $result["courses"]));
    //     } else {
    //         $result["courses"] = [];
    //     }

    //     return StudentsMapper::mapFrom($result);
    // }

    // public function update(Students $object): Students
    // {
    //     return DB::transaction(function () use ($object) {
    //         // Step 1. Create/Update audience_segments
    //         DB::table($this->tableName)->updateOrInsert([
    //             "id" => $object->getId()
    //         ], $object->toSQL());

    //         $id = ($object->getId() === null || $object->getId() === 0)
    //             ? (int)DB::getPdo()->lastInsertId() : $object->getId();

    //         return $this->get($id);
    //     });
    // }
}