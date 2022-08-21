<?php

namespace App\Imports;

use App\Person;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Row;

class PersonImport implements OnEachRow, WithEvents, WithChunkReading, WithValidation, ShouldQueue, SkipsOnFailure
{
    public $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function registerEvents(): array
    {
        return [
            BeforeImport::class => function (BeforeImport $event) {
                $totalRows = $event->getReader()->getTotalRows();

                if (filled($totalRows)) {
                    cache()->forever("total_rows_{$this->id}", array_values($totalRows)[0]);
                    cache()->forever("start_date_{$this->id}", now()->unix());
                }
            },
            AfterImport::class  => function (AfterImport $event) {
                cache(["end_date_{$this->id}" => now()], now()->addMinute());
                cache()->forget("total_rows_{$this->id}");
                cache()->forget("start_date_{$this->id}");
                cache()->forget("current_row_{$this->id}");
            },
        ];
    }

    public function onRow(Row $row)
    {
        $rowIndex = $row->getIndex();
        $row      = array_map('trim', $row->toArray());
        cache()->forever("current_row_{$this->id}", $rowIndex);
        sleep(2);

        Person::create([
            'first_name' => isset($row['first_name']) ? $row['first_name'] : null,
            'last_name'  => isset($row['last_name']) ? $row['last_name'] : null,
            'email'      => isset($row['email']) ? $row['email'] : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'first_name' => 'required',
            'last_name'  => 'required',
            'email'      => 'required|email',
        ];
    }

    public function onFailure(Failure...$failures)
    {
        return $failures;
    }

}
