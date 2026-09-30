<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ChannelCatalog;
use Illuminate\Http\Request;

class PortalApiController extends Controller
{
    public function __construct(private ChannelCatalog $catalog)
    {
    }

    public function home()
    {
        return response()->json([
            'hotel' => config('hotel'),
            'tiles' => [
                ['id' => 'channels', 'title' => 'Live TV', 'route' => '/api/countries'],
                ['id' => 'hotel', 'title' => 'Hotel Info', 'route' => '/api/hotel'],
                ['id' => 'menu', 'title' => 'Food & Beverage', 'route' => '/api/menu'],
                ['id' => 'contacts', 'title' => 'Contacts', 'route' => '/api/contacts'],
            ],
        ]);
    }

    public function countries(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $countries = $this->catalog->countries();
        if ($q !== '') {
            $countries = array_values(array_filter($countries, function ($country) use ($q) {
                return str_contains(strtolower($country['name'].' '.$country['code']), strtolower($q));
            }));
        }

        return response()->json(['data' => $countries]);
    }

    public function channels(Request $request)
    {
        $country = (string) $request->query('country', 'TZ');
        $q = trim((string) $request->query('q', ''));
        $page = (int) $request->query('page', 1);
        $result = $this->catalog->page($this->catalog->forCountry($country, $q), $page);

        $result['data'] = array_map(function ($channel, $offset) use ($result, $country) {
            $index = (($result['page'] - 1) * $result['per_page']) + $offset;
            $channel['index'] = $index;
            $channel['play_url'] = url('/stream?url='.urlencode($channel['url']));
            $channel['country'] = strtoupper($country);
            unset($channel['url']);

            return $channel;
        }, $result['data'], array_keys($result['data']));

        return response()->json($result);
    }

    public function hotel()
    {
        return response()->json(config('hotel'));
    }
}
