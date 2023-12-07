<?php

namespace Xiaoshouchen\ShortVideoAudit\Tests;

use Xiaoshouchen\ShortVideoAudit\Audit;

class AuditTest extends BaseTest
{

    public function testQueryVideo()
    {
        $this->assertTrue(1 == 1);
    }

    /**
     * @throws \Exception
     */
    public function testCreateVideo()
    {
        $sdk = new Audit("", "");
        // 调用创建视频接口
        $albumInfo = [
            'title' => '测试短剧101',
            'seq_num' => 1,
            'year' => 2023,
            'album_status' => 3,
            'qualification' => 2,
            'record_info' => [
                'license_num' => '1233456789',
            ],
            'desp' => '测试短剧101短剧简介',
            'recommendation' => '非常好看的短剧',
            'tag_list' => [1],
            'cover_list' => ['7307235131578974747'],
        ];
        $response = $sdk->createVideo($albumInfo);
        var_dump($response);
        $this->assertTrue($response["err_no"] == 0);
    }

    public function testUploadResource()
    {
        $this->assertTrue(1 == 1);
    }
}
