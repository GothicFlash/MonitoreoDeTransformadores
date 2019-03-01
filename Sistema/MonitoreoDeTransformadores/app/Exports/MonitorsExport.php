<?php

namespace App\Exports;
use DB;
use App\Monitor;
use App\Transformer;
use Maatwebsite\Excel\Concerns\FromCollection;

class MonitorsExport implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function __construct(Monitor $monitor, Transformer $transformer)
    {
      $this->monitor = $monitor;
      $this->transformer = $transformer;
    }

    public function collection()
    {
      $idMonitor = $this->monitor->id;
      $idTransformer = $this->transformer->id;
      $registers = DB::select("SELECT
                                    monitors.id AS Monitor,
                                    transformers.id AS Transformer,
                                    registers.date AS Date,
                                    gas.name AS Gas,
                                    binnacles.hour AS Hour,
                                    binnacles.ppm
                               FROM
                                    binnacles,
                                    gas,
                                    detail_gases,
                                    registers,
                                    monitors,
                                    transformers,
                                    detail_transformers
                               WHERE
                                    monitors.id=detail_transformers.monitor_id AND
                                    detail_transformers.transformer_id=transformers.id AND
                                    transformers.id=registers.transformer_id AND
                                    registers.id = binnacles.register_id AND
                                    binnacles.gas_id = gas.id AND
                                    gas.id = detail_gases.gas_id AND
                                    detail_gases.monitor_id = monitors.id AND
                                    monitors.id=$idMonitor AND
                                    detail_transformers.transformer_id = $idTransformer");
      return collect($registers);
    }
}
