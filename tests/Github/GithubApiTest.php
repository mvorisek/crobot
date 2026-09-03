<?php

declare(strict_types=1);

namespace Mvorisek\Crobot\Tests\Github;

use Atk4\Core\Phpunit\TestCase;
use Mvorisek\Crobot\Github\GithubApi;
use PHPUnit\Framework\Attributes\DataProvider;

class GithubApiTest extends TestCase
{
    public function testListBranches(): void
    {
        $githubApi = new GithubApi();

        $res = $githubApi->listBranches('mvorisek/crobot');
        self::assertArrayHasKey('main', $res);
        self::assertIsString(array_key_first($res));

        $res = $githubApi->listBranches('openclaw/openclaw');
        self::assertGreaterThan(2_000, count($res));
    }

    public function testListTags(): void
    {
        $githubApi = new GithubApi();

        $res = $githubApi->listTags('opentofu/manifesto');
        self::assertSame([], $res);

        $res = $githubApi->listTags('sebastianbergmann/phpunit');
        self::assertArrayHasKey('13.3.2', $res);
        self::assertIsString(array_key_first($res));
        self::assertGreaterThan(1_000, count($res));
    }

    /**
     * @dataProvider provideListLastCommitsCases
     *
     * @param list<string> $expectedShas
     */
    #[DataProvider('provideListLastCommitsCases')]
    public function testListLastCommits(string $repo, string $commitSha, ?int $maxCount, ?\DateTime $minDt, array $expectedShas): void
    {
        $githubApi = new GithubApi();

        self::assertSame($expectedShas, array_keys($githubApi->listLastCommits($repo, $commitSha, $maxCount, $minDt)));
    }

    /**
     * @return iterable<list<mixed>>
     */
    public static function provideListLastCommitsCases(): iterable
    {
        yield ['mvorisek/crobot', '95f25881c55a6419605c0df2b86672d44bdda0ed', null, null, [
            'fa9601a75aa5d946d00edf7e98a9f3b65133af22',
            '95f25881c55a6419605c0df2b86672d44bdda0ed',
        ]];

        yield ['mvorisek/crobot', '95f25881c55a6419605c0df2b86672d44bdda0ed', 1, null, [
            '95f25881c55a6419605c0df2b86672d44bdda0ed',
        ]];

        yield ['mvorisek/crobot', '95f25881c55a6419605c0df2b86672d44bdda0ed', 3, null, [
            'fa9601a75aa5d946d00edf7e98a9f3b65133af22',
            '95f25881c55a6419605c0df2b86672d44bdda0ed',
        ]];

        yield ['mvorisek/crobot', '95f25881c55a6419605c0df2b86672d44bdda0ed', null, new \DateTime('2026-09-02 11:09:39 UTC'), [
            'fa9601a75aa5d946d00edf7e98a9f3b65133af22',
            '95f25881c55a6419605c0df2b86672d44bdda0ed',
        ]];

        yield ['mvorisek/crobot', '95f25881c55a6419605c0df2b86672d44bdda0ed', null, new \DateTime('2026-09-02 11:09:40 UTC'), [
            '95f25881c55a6419605c0df2b86672d44bdda0ed',
        ]];

        yield ['sebastianbergmann/phpunit', '4f1be6d3c782b1290de3753192d0a58549f2dba9', 210, null, [
            '2bf4722844393fd9b5547f88458939327a77615c',
            '5d2afe181339a56348ef9a80fa7eb806b7eae508',
            '902dce568f7733844ece7c932f4dbddcd57faae5',
            '38bb85c50c53a08d7d4a6a9052cbb9814504099d',
            '3f2a83c0932dd3ca7d58a20fe8f24765fea514a0',
            '603c565d0bb3f70cd9333cfc6bd13b7dd7b335a4',
            '65e8850db756b1aadac044d1faffc1820c0afccb',
            '1d5b9af8d830572dd83dc6c19356de88c62eb876',
            'd48ef7998bdb2c90f262444aebb0a04e111c6bee',
            '12fdfab37c9693dc8fbc7520fa7a96d80142590c',
            '2a80821929f2f9b9609c982ddd63a3e0bdb4081b',
            '9b6a07e5cc3aa897ac03ea73a84283037d16aa1b',
            '82a1c111b997971808518efdea880ec1d6aff153',
            '08b176aaf60cd6a32b9e444f3af4dc5ca37a97bc',
            '6d612ad61747826d06212207af4a220f58b5098a',
            'fe14e859c005b1a52b8aa309f802f2efa5e186e8',
            '5d4560b6c27f9f7105bf7d878299f65f643129bf',
            'e4b3f1fa620e1a81604d3488ac5d10aa2cad3a08',
            '97f774e5b1b15158094abe5c6905bb631b34ef25',
            '54994ec42d5829be4341bc86523d72237b32e1af',
            '97e327894a9180b01de6958a36568ea9249d0120',
            '097b043d79f227cd84aa13dd43851243ac17cf1c',
            '2ebe468f51f197271089c0e48eed46d72035c1be',
            '49f0292a634fe4adea06a1ee0cad892e8e35c328',
            '7bfaf155431c5bf193dc28285a314b909d09a35b',
            '8728a839145b3eebdca5d7b0c2899c18377f1dfc',
            '98e3b9f59db9d854c36fa189e9567e0b044515c8',
            '04a848cd68bde40772abbc6c15424d390c981860',
            'f11d8b7b7dd56e514878309c12b3a4eb9868d9c9',
            '7f382a2d4b9ccfcd65a8ed0c78e9aecafa5f8fe4',
            'fd178da7b6778a3f7e495a7ed0735e57c74753f7',
            '7763b5e069167b67f0b9af27098bc2839a01f439',
            'd0df50de301881d5e4ba4a6ba1c422f668e1584f',
            'f3a5f27f70fe7b1a1f0e6bc5e92795d6c842b89d',
            '297c78acdcd896a64ae73927408151654a01c995',
            '53e5fbb439499e05798ca7590296f75404734c5f',
            '20c7dd68a6e15a4c358b15d0c06dce2ffd5684a9',
            '2b9331f7854ab4cc2296ce0307f7d604499d3a0a',
            '27074c9e89c52b5292a6657015349a6b9c6a5f45',
            '6d0efdeef1357a5a7e262bfab3a691e58831b6b2',
            'ba123b5a105898b8fa5c8ec98c272f1dc1fb0bda',
            '2bc813adc2e89f4f3e62dbc9be46301e578b7fe6',
            'de077c45a69e783a1ef2c3224448569c9a993086',
            'cd17c6004b9b4803dc184f1d3e05fd84173619ed',
            'ed3c1f6a78978de89917f41606c4471a1bbf00f8',
            '00a93e3a2351ffdcd7f3672de1f1f713ff459157',
            '8d18db34da6fb6141adb5c8e7c8e0b6967ebc2e1',
            '7dbe24428766da07862fecb8f573864ca11609fb',
            'c123b1c76dfc65a08000b3dabdbdb17e08162184',
            'fa28a1263ea08740ee3b293192db451e8b8482e1',
            'c238f6f5a0816deef17d4d2ad7b2ef4554dabdf7',
            '9dc22efda395abc7cd14f0fb7058ec100f46d331',
            '3982f94b645e4048ca9f2e3fc739202bbbfacced',
            'ec0b23f1a7137df16612541f087e002836f45694',
            '690306ef8d08c68b8cdb9904a0782027da1876b0',
            '6b31c8bac23c08c58a1da38e1126c77461c24c94',
            'a157d2316e9e15eb166f4c3fd35aab5bb8d0adae',
            'dfa6e76ad89d6b40243a3ae77f5fa9da80fd28ba',
            'b9e48663ef5da8e2a7a59af4b8dcce41152253f0',
            '63e7d6883220479b620ec3c9a4b25e93a53508d9',
            '357b8199fbed481141c975ee3b21de5b38f5419b',
            'd5cff89a195f6f8385fe613f4bf806a169c2fcbc',
            '8f3fae933feff386b4a2e22f9cc353670dff67c8',
            '46cbdb86f8b68cb7d8f29ed52f9515cac75704c3',
            '824822b11d18c0feac16a6bbdaaa66f2131ff88d',
            'fabfe154da13717cde79b6346a6840525ccaf648',
            '6c74bc15fe2bd41906b9191c5ca493bd0a16424b',
            'c7ffde6e9a19fe37466aa3ddfc3755cc7a432796',
            '05e730fe55074f7187b8457fe3191d06dc3686fa',
            'b44cfea3c4170ad61e3513d90fa43ede69e23b36',
            '6891acb53aac4ae2b40ee935be0af08635661079',
            'ffb794cf4a26c6268d35ce8e23c64f4d2cfd4828',
            'cb04426f4ababb1e92c56137602d1ad081c7a468',
            '65b955e03517bcf00af56439a13342e54d71802d',
            '61b0d92911a61df8af73002ac33442d6723c9ac9',
            '0181c16376ec24379de81414ab6429ffa65b8ee3',
            'dd3e702f7793a6b98ab60b2919854e8246442a21',
            '6d0f2766cd373cec89a06801591f9b2fe2d85a40',
            '8dc4a4f4921520293900fa2e935f8f641f874c8b',
            'df4cdb19afe003efbf43503ea3fc8050a530d2c9',
            '0b3122d442b5d252a949df6278b85bff6275807e',
            'bd5e8195941307c1ee381e778637f2e5b41e0080',
            '4d35bebaf5855a09a76e1fedba8cb12ff7fc1d50',
            '11932138206d836eeca903fe3eff2f25c35c57b9',
            'fdcc32a0c16122a904b997f09609f503ca25518b',
            '88e68d11bac0de2c9af530627fe5e02b84573173',
            '51ece9d7b41967cfb5f2f2c3e906c5b8de425922',
            '17ac9f629c1e7ebe4473bda4c16f20542beea227',
            'f121d56765a7bee92b22f0a2b4e193f21c15e0d0',
            '828d62c98b3a0b674bd250517e3e0b8688acfdd0',
            '77af2e2b5b2e6eb21a02251b35480378333a81a5',
            '346fcba6ce7ab89bb1b0675feac6bc29c0f7711b',
            'e0d3abf6610fd161be04287a8b1f48046ec5071b',
            '06c368a0401631912ce307be110fc58d3e2541d5',
            '6917e76ff5762f4b70203b58608f6bba360cc2c2',
            '2b9391f2c8800bc8ede6a23770e125a94e71b7d5',
            'c8d6921fb601b6988854cd84a672f06c1e49f80e',
            '9dc60c34664fdfb6bff64501b7655e3f96e369b5',
            '5d20f133bd67d571cb27f853627169410eb6a3da',
            'dbc02ef2c6d12773f30525e8e526d5d45aaa3cc2',
            'a2a1a3f4750025e38b7f785a8326999362023d0a',
            'bbd3c831733f0cde10220d1c759816ba48269103',
            '9375d10d1e5dbbb7209876c42625bbe82df2928f',
            '83be0fc01fa84547d3d3b56013bee057ba1925a7',
            'a2048e9ab633c1e2623a230fd9dd42313cf516e0',
            '7efd432843b42b95c4ccb3e648f2489ad1691653',
            '675e56e15113b1afad98599ab607483a0a02c692',
            '04157ce2484de19b592e951450d7f4302f1836ff',
            'b343f0f1b35be04ab17ce93e42d1dc269d5a8259',
            'e29a7040361f3f4a9877e6822524bd13c75eccd2',
            '757d6b17ecfd7fcf4e5487bf7939ee02f77743f7',
            'fc024931d6ad047404e9d86536735923fe63a06b',
            'bf706078330c956607a2354d33caf414d1e23348',
            '2e9df65d413a01a59edf74cc86c68f8e8216d1a5',
            'd3368ff36e6d5ea65551836795695f60393a3d9a',
            '35c032b8c3774a4d2092df143694e18f3538cf39',
            'bde26096c8138bc1d16d9231ba4e46e544f410a3',
            '7d09efa406a73f86df755fd79838e69ce6a9d92e',
            '35eb17af84a859ca9aca45aaf1157598d2b12257',
            '0fbd49488212812defa1ec717c6b08cebac34e49',
            '219d8d239317ffc34e91cf7db53b6cf01c8bd343',
            'aae51f8f32b14a2201da8f9398597764f384a0ff',
            '771fd8de39a13c7952f3a8d6918025466327a8b5',
            '37984923c80b6cfff44f30e3e3da76581e6f35b3',
            '3ea07af5ae6b24093bbdc1e1e9f53cae352e6476',
            '2753cf3ad7b0d88fa912d8798532f47cce1c0fda',
            'f68e50d32d3960030d7ccfb403b367ad6045dd6a',
            '8181a55471d0c8404de3497a9383c3c3d73df90c',
            '5518c5c26ee638d89825bbaa95d7ce829891cf29',
            'fc68a9867059ac38fa36462876d2a99088795c73',
            '006da4142f234221e7b44c8c75253245d13b26a6',
            '329e691711824dc7c8414ac050ea3b3d39793f0c',
            '0543eccaaa9a9f582b924b1fb8286b8a1256db09',
            'e1fc634f08bca89edbdc3cff3ef575ee7e3c02e6',
            '3c46dc6d5321d10faf910cd470778f920007640f',
            '2652e4464813b6bb63ee4e8fdecfe68185e0b00f',
            'c7832e86617ba182c7aa8f8b41f1b4152207cb12',
            'f0f96e945c495ea5519d6b26df1b095dd1cada60',
            '841bbfeb6ffd4bf2aef323778f6e1c3fad70f4d3',
            'ae51ae43b169466e44618077116bb6383ad0631e',
            'ccffd4a939bd4e748ca970d27dfc1b612662bfa6',
            'ba57984f5d2b02351f2d3bfd2cde6512b00b5310',
            'ccf73f3ab6d5056815f0f0ce4f0be94b7c17a756',
            '0a3b402e122774ba15c42591300151d86977976a',
            'aa41ca3a57145b344272a058226cfa1ca25746a2',
            'dfe625c53c97807fb6ee8199bd3b8323d90fcefb',
            '682d98d9e571b33f7196d73967af63c4ce683a02',
            'f0bc23d319ca384aa061adb65f19014f5798c0a2',
            'defe0f56e89d820bde051491451037c80be59577',
            'a36b6798035c56d63206e3d753265661a1987e8b',
            '2ebabb63385212f4e3e2ee278649dff626a43f31',
            '462e73663be5884d90a531bb5231e9815cbde195',
            'da8601bb2c899fad3c3e227c830fc45274f1970b',
            '88101a6039fb328e1f7139b87aa015ea168d57c0',
            '049cd82e00b939231f6ccc0fe1fef3665f465599',
            'f123cdb2a2d49f15025794166cfed8bda8627dd2',
            '45544b20764d5802107736ebe53b8e455169c1c7',
            '29f48750b725dfa55b2f06a0d822dbc55eb49907',
            'c5396e954e837271e4ce3f5d6a6f60d195da431e',
            '4d0a992f85ebd0c695868ef00135bf049f346e37',
            '9bc522b20a2868f1bf252814d322ebdcaca19c7d',
            '5f8731791366cc51412f83b252c72eaf90d1f902',
            '2bc36a3967a905b3c56d4861717bf8d37cac0529',
            'ed55b3a86f3c12861513f1642b7ed35e0b43d27d',
            '29d42e6b0031037ccfe435517dd2fe8a73eef806',
            '45b1b96c93a9aacaac077409bc6fb83d764778b2',
            'f06aea49a2848ecbb326d27af06850fca9403ee9',
            'e233b8c78939075248240b3d4824c75a936cfd32',
            '5b0c0255240a49951c058fa6e24e5e792fc71f1e',
            '231c11679ba4d8a3bfa3ee738ef840843cbd8dd4',
            '1615b85bced6fd6427690ea65e9ae20e378376cd',
            '635679260a019266451bc8af968a2ba0149b2e11',
            '759f37c77c4e8925ace764b985f9977ab2e65be2',
            '0d6b5221bd07386c5f77c2dbab8cf387b2a51930',
            '52166d7abe0d7275b6508bc1a97f7efbc52b4ebc',
            '76b1c89e925637703e46ebc631d1cdd419abcc3b',
            '2afb6f73f094220d741725241d969b06ee2514e9',
            '9a070fdd702dbc3162581e02ddb51ba9103cfded',
            '85660dc4e783136ae75745ff7bc544024c1d6ee0',
            '20921d932420dd756331a2cae657b7b528933c4d',
            'bdfc83e61cddcb741159cd5d24348946e714a03a',
            '402fdcf68e4c70993cb75796ec7fbac3dc0f47cb',
            '41ad3fa9b03f5b385e2d7f0e5f3945c89a9467e7',
            '3e06a1f7f64ec4adabe46aa935a29fba2e6df129',
            '2da9ae8a3d463545b2f9ca8967c24e1be8249bc4',
            'd8bf712f5251da9750f704e3c24fb5480583ca8a',
            'b142494d8c2181cc61d255ef639feb3c9d9baaee',
            '6cbff63d670de92cb1cb3d2ff9f40327e9da9c7f',
            '7da33db2f1f30189348182e696cd3dc271f185a0',
            '22104a5ceb8d642e6b30ab00211d53d88e2db368',
            '101056bad9483c247ad3e04418ac84dc9862452b',
            'd3fce5fa86fe76341fb16631c62b3ebf68423b21',
            '47f1980aebdcfcd3e33c235e84019e2b7b3830eb',
            '8d121f17c93003b0a121626654ecd243cc940232',
            '7df8892ad609276cd0119f0e6182530f28b9312c',
            '8521f5b1ad11cd78b6a7e0e271b37f017f8886f6',
            '7440a3ed1a3c39d5a60fe9e6cb9d01025da15477',
            'e32013ff65a90c822b337f9288fb143fe20ede7a',
            '61bcb659a45e71eba5f3c7de02066d6908130807',
            'd7ecc9ab0352991b8c09f64041eaa03091ef65bd',
            '853fae2e62af84f322f34c6bdb4efc082365f856',
            '3939a35339e738483b2d630c2386c60f209b52b7',
            'a89e7e16e7db265e39c2d6b1611ab00f7fd9dc79',
            'd30418045917a3f8c4818fc67706142b0da9df47',
            '943a8f39ea1194074cb89d1c5af6da39c23fbec2',
            '08da0e25489f1f576539407c22a1375489c8bf72',
            '576daf18f04162be0db9a3681878e72e65d61af6',
            'c625625cc4222f339c3e3a661f14e8663d708de0',
            'f27c338739598755390f9209bb13bb0322095dc3',
            '4f1be6d3c782b1290de3753192d0a58549f2dba9',
        ]];

        yield ['sebastianbergmann/phpunit', '4f1be6d3c782b1290de3753192d0a58549f2dba9', 210, new \DateTime('2026-08-31 00:00:00 UTC'), [
            'c625625cc4222f339c3e3a661f14e8663d708de0',
            'f27c338739598755390f9209bb13bb0322095dc3',
            '4f1be6d3c782b1290de3753192d0a58549f2dba9',
        ]];
    }
}
