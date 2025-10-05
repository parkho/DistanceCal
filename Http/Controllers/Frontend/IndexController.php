<?php

namespace Modules\DistanceCal\Http\Controllers\Frontend;

use App\Contracts\Controller;
use Illuminate\Http\Request;
use App\Repositories\AirportRepository;
use Illuminate\Support\Facades\Http;
/**
 * Class $CLASS$
 * @package 
 */
class IndexController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function index(Request $request)
    {
        $airports = $this->airportRepo->all(); // get all airports
		return view('distancecal::index', compact('airports'));
    }

   protected $airportRepo;

    public function __construct(AirportRepository $airportRepo)
    {
        $this->airportRepo = $airportRepo;
    }

    public function calculate(Request $request)
    {
        $depicao = strtoupper($request->query('depicao'));
        $arricao = strtoupper($request->query('arricao'));

        if (!$depicao || !$arricao) {
		    flash()->error('Both ICAO codes are required');         
		    return back(); 
		    // response()->json(['error' => 'Both ICAO codes are required'], 400);
		}

        $dep = $this->airportRepo->findWhere(['icao' => $depicao])->first();
        $arr = $this->airportRepo->findWhere(['icao' => $arricao])->first();

        if (!$dep || !$arr) {
		    flash()->error('Airport not found');         
		    return back(); 
		    // return response()->json(['error' => 'Airport not found'], 404);
		}

        $distance = $this->haversineGreatCircleDistance(
            $dep->lat, $dep->lon,
            $arr->lat, $arr->lon
        );

        // flight time (450nm/h average + 30 min taxi)
        $cruiseSpeed = 450;
        $blockTime = ($distance / $cruiseSpeed) * 60 + 30;
        $hours = floor($blockTime / 60);
        $minutes = round($blockTime % 60);
        $formatted = sprintf('%02d:%02d', $hours, $minutes);

        return response()->json([
		'distance' => round($distance, 1),
		'flight_time' => $formatted,
		'dep' => [
			'lat' => $dep->lat,
			'lon' => $dep->lon,
			'icao' => $dep->icao,
			'name' => $dep->name,
		],
		'arr' => [
			'lat' => $arr->lat,
			'lon' => $arr->lon,
			'icao' => $arr->icao,
			'name' => $arr->name,
    ],
]);
    }

    private function haversineGreatCircleDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 3440.065; // Nautical miles
        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return $angle * $earthRadius;
    }
}
