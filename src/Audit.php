<?php

namespace Xiaoshouchen\ShortVideoAudit;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Xiaoshouchen\ShortVideoAudit\enum\CacheKey;
use Xiaoshouchen\ShortVideoAudit\enum\Resource;

class Audit
{
    /**
     * @var Client
     */
    private $client;
    private $clientKey;
    private $clientSecret;
    private $token;

    /**
     * @param $clientKey
     * @param $clientSecret
     * @param $token
     * @throws \Exception
     */
    public function __construct($clientKey, $clientSecret, $token = null)
    {
        $this->clientKey = $clientKey;
        $this->clientSecret = $clientSecret;
        $this->token = $token;
        $this->client = $this->getToken();
    }

    /**
     * 获取token
     * @return Client
     * @throws \Exception
     */
    private function getToken(): Client
    {
        $token = $this->token ?? Cache::get(CacheKey::TOKEN);
        //var_dump("获取默认token" . $token);
        if (empty($token)) {
            //var_dump("未获取到token");
            try {
                $response = (new Client())->request('POST', 'https://open.douyin.com/oauth/client_token/', [
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'client_key' => $this->clientKey,
                        'client_secret' => $this->clientSecret,
                        'grant_type' => 'client_credential',
                    ],
                ]);
            } catch (\Throwable $exception) {
                Log::info($exception->getMessage());
                throw new \Exception($exception->getMessage());
            }

            $data = json_decode($response->getBody(), true);
            $token = $data['data']['access_token'];
            // 提前一分钟重新获取，防止token过期
            Cache::put(CacheKey::TOKEN, $token, now()->addSeconds($data['data']['expires_in'] - 60));
        }
        //echo "返回token:" . $token;
        return new Client([
            'base_uri' => 'https://open.douyin.com/api/',
            'headers' => [
                'Content-Type' => 'application/json',
                'access-token' => $token,
            ],
        ]);
    }

    /**
     * 上传资源
     * @param $resourceType
     * @param $resourceMeta
     * @return mixed
     * @throws GuzzleException
     * @throws \Exception
     * @example
     * image
     * "image_meta": {
     * "url" : "https://lf3-static/xxxx.png"
     * }
     *
     *  video:
     * "video_meta": {
     * "title" : "视频标题",
     * "description": "视频内容描述",
     * "url" : "https://lf6-developer-sign.bytedance.net/h5/xxxx.mp4",
     * "format": "mp4"
     * }
     */
    public function uploadResource($resourceType, $resourceMeta)
    {
        $endpoint = 'playlet/v2/resource/upload/';

        $payload = [
            'resource_type' => $resourceType,
            'ma_app_id' => $this->clientKey,
        ];
        switch ($resourceType) {
            case Resource::TYPE_VIDEO:
                $payload['video_meta'] = $resourceMeta;
                break;
            case Resource::TYPE_IMAGE:
                $payload['image_meta'] = $resourceMeta;
                break;
            default:
                throw new \Exception("资源类型错误");
        }

        $response = $this->client->request('POST', $endpoint, [
            'json' => $payload,
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * 查询短剧
     * @param int $videoIdType
     * @param int $openVideoId
     * @return array
     * @throws GuzzleException
     */
    public function queryVideo(int $videoIdType, int $openVideoId): array
    {
        $endpoint = 'playlet/v2/video/query/';

        $payload = [
            'ma_app_id' => $this->clientKey,
            'video_id_type' => $videoIdType,
            'open_video_id' => $openVideoId,
        ];

        $response = $this->client->request('POST', $endpoint, [
            'json' => $payload,
        ]);

        return json_decode($response->getBody(), true);

    }

    /**
     * @param array $albumInfo
     * @return array
     * @throws GuzzleException
     * @example
     * "album_info" => [
     * "title" => "测试短剧101",
     * "seq_num" => 1,
     * "year" => 2023,
     * "album_status" => 3,
     * "qualification" => 2,
     * "record_info" => ["license_num" => "1233456789"],
     * "desp" => "测试短剧101短剧简介",
     * "recommendation" => "非常好看的短剧",
     * "tag_list" => [1],
     * "cover_list" =>"7307235131578974747"]];
     **/
    public function createVideo(array $albumInfo): array
    {
        $endpoint = 'playlet/v2/video/create/';

        $payload = [
            'ma_app_id' => $this->clientKey,
            'album_info' => $albumInfo,
        ];

        $response = $this->client->request('POST', $endpoint, [
            'json' => $payload,
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * 编辑短剧/添加剧集
     * @param $albumId
     * @param $albumInfo
     * @param $episodeInfoList
     * @return array
     * @throws GuzzleException
     */
    public function editVideo($albumId, $albumInfo, $episodeInfoList): array
    {
        $endpoint = 'playlet/v2/video/edit/';

        $payload = [
            'ma_app_id' => $this->clientKey,
            'album_id' => $albumId,
            'album_info' => $albumInfo,
            'episode_info_list' => $episodeInfoList,
        ];

        $response = $this->client->request('POST', $endpoint, [
            'json' => $payload,
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * @param int $albumId 短剧id
     * @return array
     * @throws GuzzleException
     */
    function reviewVideo(int $albumId): array
    {
        $endpoint = 'playlet/v2/video/review/';

        $payload = [
            'ma_app_id' => $this->clientKey,
            'album_id' => $albumId,
        ];

        $response = $this->client->request('POST', $endpoint, [
            'json' => $payload,
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * @param $albumId
     * @param $operate
     * @param $version
     * @return array
     * @throws GuzzleException
     */
    public function onlineAlbum($albumId, $operate, $version): array
    {
        $endpoint = 'playlet/v2/album/online/';

        $payload = [
            'ma_app_id' => $this->clientKey,
            'album_id' => $albumId,
            'operate' => $operate,
            'version' => $version,
        ];

        $response = $this->client->request('POST', $endpoint, [
            'json' => $payload,
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * @param $queryType
     * @param $limit
     * @param $offset
     * @return array
     * @throws GuzzleException
     */
    public function fetchAlbums($queryType, $limit, $offset): array
    {
        $endpoint = 'playlet/v2/album/fetch/';

        $payload = [
            'ma_app_id' => $this->clientKey,
            'query_type' => $queryType,
            'batch_query' => [
                'limit' => $limit,
                'offset' => $offset
            ]
        ];

        $response = $this->client->request('POST', $endpoint, [
            'json' => $payload,
        ]);

        return json_decode($response->getBody(), true);
    }
}
