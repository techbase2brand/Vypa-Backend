<?php

namespace Marvel\Http\Controllers;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Marvel\Database\Models\Address;
use Marvel\Database\Repositories\SettingsRepository;
use Marvel\Events\Maintenance;
use Marvel\Exceptions\MarvelException;
use Marvel\Http\Requests\SettingsRequest;
use Prettus\Validator\Exceptions\ValidatorException;

class SettingsController extends CoreController
{
    public $repository;

    public function __construct(SettingsRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return Collection|Address[]
     */
    public function index(Request $request)
    {
        try {
            $language = $request->language ? $request->language : DEFAULTNGUAGE;

            // Get data directlrom repository without caching temporarily
            $data = $this->repository->getData($request->language);

            // Format maintenance start and until data
         if (isset($data['options']['maintenance'])) {
                $maintenanceStart = Carbon::parse($data['options']['maintenance']['start'])->format('F j, Y h:i A');
                $maintenanceUntil = Carbon::parse($data['options']['maintenance']['until'])->format('F j, Y h:i A');

                $formattedMaintenance = [
                    "start" => $maintenanceStart,
                    "until" => $maintenanceUntil,
                ];

                // Add formatted maintenance data to the existing data
                $data['maintenance'] = $formattedMaintenance;
            }

            return $data;
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch settings',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // public function fetchSettings(Request $request)
    // {
    //     $language = $request->language ? $request->language : DEFAULT_LANGUAGE;
    //     return $this->repository->getData($language);
    // }

    /**
     * Store a newly created resource in storage.
     *
     * @param SettingsRequest $request
     * @return mixed
     * @throws ValidatorException
     */
    public function store(SettingsRequest $request)
    {
        $language = $request->language ? $request->language : DEFAULT_LANGUAGE;
        $request->merge([
            'options' => [
                ...$request->options,
                ...$this->repository->getApplicationSettings(),
                'server_info' => server_environment_info(),
            ]
        ]);

        $data = $this->repository->where('language', $request->language)->first();

        if ($data) {
            $settings = tap($data)->update($request->only(['options']));
        } else {
            $settings = $this->repository->create(['options' => $request['options'], 'language' => $language]);
        }

        return $settings;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param SettingsRequest $request
     * @param int $id
     * @return JsonResponse
     * @throws ValidatorException
     */
    public function update(SettingsRequest $request, $id)
    {
        $settings = $this->repository->first();
        if (isset($settings->id)) {
            return $this->repository->update($request->only(['options']), $settings->id);
        } else {
            return $this->repository->create(['options' => $request['options']]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return array
     */
    public function destroy($id)
    {
        throw new MarvelException(ACTION_NOT_VALID);
    }
}
