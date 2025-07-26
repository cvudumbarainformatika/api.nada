<?php

namespace App\Http\Controllers;

use App\Models\Magama;
use App\Http\Requests\StoreMagamaRequest;
use App\Http\Requests\UpdateMagamaRequest;

class MagamaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreMagamaRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreMagamaRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Magama  $magama
     * @return \Illuminate\Http\Response
     */
    public function show(Magama $magama)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Magama  $magama
     * @return \Illuminate\Http\Response
     */
    public function edit(Magama $magama)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateMagamaRequest  $request
     * @param  \App\Models\Magama  $magama
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateMagamaRequest $request, Magama $magama)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Magama  $magama
     * @return \Illuminate\Http\Response
     */
    public function destroy(Magama $magama)
    {
        //
    }
}
