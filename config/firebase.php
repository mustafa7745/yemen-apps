<?php

declare(strict_types=1);

return [
    /*
     * ------------------------------------------------------------------------
     * Default Firebase project
     * ------------------------------------------------------------------------
     */

    'default' => env('FIREBASE_PROJECT', 'app'),

    /*
     * ------------------------------------------------------------------------
     * Firebase project configurations
     * ------------------------------------------------------------------------
     */

    'projects' => [
        'app' => [

            /*
             * ------------------------------------------------------------------------
             * Credentials / Service Account
             * ------------------------------------------------------------------------
             *
             * In order to access a Firebase project and its related services using a
             * server SDK, requests must be authenticated. For server-to-server
             * communication this is done with a Service Account.
             *
             * If you don't already have generated a Service Account, you can do so by
             * following the instructions from the official documentation pages at
             *
             * https://firebase.google.com/docs/admin/setup#initialize_the_sdk
             *
             * Once you have downloaded the Service Account JSON file, you can use it
             * to configure the package.
             *
             * If you don't provide credentials, the Firebase Admin SDK will try to
             * auto-discover them
             *
             * - by checking the environment variable FIREBASE_CREDENTIALS
             * - by checking the environment variable GOOGLE_APPLICATION_CREDENTIALS
             * - by trying to find Google's well known file
             * - by checking if the application is running on GCE/GCP
             *
             * If no credentials file can be found, an exception will be thrown the
             * first time you try to access a component of the Firebase Admin SDK.
             *
             */

            'credentials' => [
                "type" => "service_account",
                "project_id" => "yemen-stores",
                "private_key_id" => "d9befb63dcc2829df46737c748c5ad7d02f52d19",
                "private_key" => "-----BEGIN PRIVATE KEY-----\nMIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQDTC4lCACsehefi\n6sb6vIaNd2OWbuxP8i10AmsD0Flva9SVDuSMsIu+kmCEqk6RHsGqtj4ccfJp1cW7\nekpidchGCtKGReo3FMsnWjnKBoraD9AsysLZtH5DEa78KNPsWHlo6hVz5F0X5jGS\nJHkAI9sQp47qT7kI9HEeYiXR6FIeeqLVHzr2qx0cfFY9r2g0YMyLSoIfKWkzhN1A\n6B197B/MR1teWs3KvJVWgfnH1rKRKdKzyo2W0OjELxDU0Q+R1rQ8CUk+NcKbKtO3\nPVei2Rjv8D43jF/7fqSpS4twcknrvqScX/e9puQHvjCW+/aolbxEHThXtZMTb78x\n2hnHGcrXAgMBAAECggEAFKVxq+niTgRUyo4KiBsLbfZUR5im0hQwi10Oy3rr7orC\nnqmsHqT8dtjqslY+HqC/DfLx1P/DJffXQt9uCX9zGuiru2qsxO8dOyzur5HzFLqS\n56uwyXJVhXAxPNMtsFbgQcasvjK4D/XhuzKJ190QhreUGd4eWjIeIuMAYJTblV/4\ntE6PO9lrh4REhdKCGZKyktp5lpC9K2dCZNa/ja6z51R5q9Y5oMTGvLrpGDVHazwk\nvZMMd1mQv6crhjpl2jo/3P25HfXPpaS5V/mjvEyW9mNvoQMVHcxOVFiTF5OB+m5h\nDLYp7Pe8EloJ//YHv5j1A24EgInRDQOaPX/b2ksWmQKBgQD/jjC+76W9A9rM5V0x\n5kPVatgRmkBpY5lLrThkVnZmtF/xKjIHXXkyI7snD3mXEe8IptqNM0bhWhGUdpMm\nAjvmlT4TanfPNBvqchDJeIMgkdq4Nken1fvuPU+1z/GcGF7sx/GnlDUkpykWheRE\n1PVbIIw7DVTr1srdXkM3r/Hq3QKBgQDTaYX+NATJCCYCzhgYQmf2PgY/ICP8G+16\nNOO8I7x6QIMxfgYbNDyUUpUDzdKRsJ18hyTt+069FxUT4+s5cxt9j4IAFmbyCvWe\n6y7ZzyASXLHSAvseBpzcZGCKpHrFd0yzYtc4hMyXmL/8xFcOsTAG8rdU88BIilPb\nNdGGTcnvQwKBgFbg+CFxR18i2FegAjbcmWMMl7gkQJGTkqHvmaRC4K251IQgXDG0\nzWcGTrHQyP1a03CViOdH72jdPezDAvOA/uw9AIWJRIHkrTje3mYf2jRQYZMOoP2l\n+afcoCSnNPRkNKE6uCTIdeioC4fkrN3ZqC/6uLG6rowe0YjAawmbfxrhAoGABmCj\niySUlF/rjaAb9/dg3XvHgnX8v+kzw8D+sbk+QU3a505O7tknjq3jEudNl9mFFrGY\n+pjfKjMdDqmMegIv7Ry8JjaGynxsJmwf0LA/3m3va09ttd0rNDbO9r+5eGV96dds\neKcA6P3RpNVjbu0Hbt45i5WC0m1h1DYOaQfFtLMCgYBCGOyvXFRFU7zdx8Y+G1VX\ngJh3MwSp/w99Vy4WgtPTLl066Um3bFbzzdwZrdbmssbxW4OhAoEKIT+RYBIMBtRB\neGgtH/HpowFvJzY80/OJAjMY8/cMco2Zga8g+5IE1Idm5PYnjIuZ8vqySsggVIDt\n2fjim53c4QJ3SjrZqHwo3g==\n-----END PRIVATE KEY-----\n",
                "client_email" => "firebase-adminsdk-7qbeo@yemen-stores.iam.gserviceaccount.com",
                "client_id" => "101185850926854167583",
                "auth_uri" => "https://accounts.google.com/o/oauth2/auth",
                "token_uri" => "https://oauth2.googleapis.com/token",
                "auth_provider_x509_cert_url" => "https://www.googleapis.com/oauth2/v1/certs",
                "client_x509_cert_url" => "https://www.googleapis.com/robot/v1/metadata/x509/firebase-adminsdk-7qbeo%40yemen-stores.iam.gserviceaccount.com",
                "universe_domain" => "googleapis.com"

            ],

            /*
             * ------------------------------------------------------------------------
             * Firebase Auth Component
             * ------------------------------------------------------------------------
             */

            'auth' => [
                'tenant_id' => env('FIREBASE_AUTH_TENANT_ID'),
            ],

            /*
             * ------------------------------------------------------------------------
             * Firestore Component
             * ------------------------------------------------------------------------
             */

            'firestore' => [

                /*
                 * If you want to access a Firestore database other than the default database,
                 * enter its name here.
                 *
                 * By default, the Firestore client will connect to the `(default)` database.
                 *
                 * https://firebase.google.com/docs/firestore/manage-databases
                 */

                // 'database' => env('FIREBASE_FIRESTORE_DATABASE'),
            ],

            /*
             * ------------------------------------------------------------------------
             * Firebase Realtime Database
             * ------------------------------------------------------------------------
             */

            'database' => [

                /*
                 * In most of the cases the project ID defined in the credentials file
                 * determines the URL of your project's Realtime Database. If the
                 * connection to the Realtime Database fails, you can override
                 * its URL with the value you see at
                 *
                 * https://console.firebase.google.com/u/1/project/_/database
                 *
                 * Please make sure that you use a full URL like, for example,
                 * https://my-project-id.firebaseio.com
                 */

                'url' => env('FIREBASE_DATABASE_URL'),

                /*
                 * As a best practice, a service should have access to only the resources it needs.
                 * To get more fine-grained control over the resources a Firebase app instance can access,
                 * use a unique identifier in your Security Rules to represent your service.
                 *
                 * https://firebase.google.com/docs/database/admin/start#authenticate-with-limited-privileges
                 */

                // 'auth_variable_override' => [
                //     'uid' => 'my-service-worker'
                // ],

            ],

            'dynamic_links' => [

                /*
                 * Dynamic links can be built with any URL prefix registered on
                 *
                 * https://console.firebase.google.com/u/1/project/_/durablelinks/links/
                 *
                 * You can define one of those domains as the default for new Dynamic
                 * Links created within your project.
                 *
                 * The value must be a valid domain, for example,
                 * https://example.page.link
                 */

                'default_domain' => env('FIREBASE_DYNAMIC_LINKS_DEFAULT_DOMAIN'),
            ],

            /*
             * ------------------------------------------------------------------------
             * Firebase Cloud Storage
             * ------------------------------------------------------------------------
             */

            'storage' => [

                /*
                 * Your project's default storage bucket usually uses the project ID
                 * as its name. If you have multiple storage buckets and want to
                 * use another one as the default for your application, you can
                 * override it here.
                 */

                'default_bucket' => env('FIREBASE_STORAGE_DEFAULT_BUCKET'),

            ],

            /*
             * ------------------------------------------------------------------------
             * Caching
             * ------------------------------------------------------------------------
             *
             * The Firebase Admin SDK can cache some data returned from the Firebase
             * API, for example Google's public keys used to verify ID tokens.
             *
             */

            'cache_store' => env('FIREBASE_CACHE_STORE', 'file'),

            /*
             * ------------------------------------------------------------------------
             * Logging
             * ------------------------------------------------------------------------
             *
             * Enable logging of HTTP interaction for insights and/or debugging.
             *
             * Log channels are defined in config/logging.php
             *
             * Successful HTTP messages are logged with the log level 'info'.
             * Failed HTTP messages are logged with the log level 'notice'.
             *
             * Note: Using the same channel for simple and debug logs will result in
             * two entries per request and response.
             */

            'logging' => [
                'http_log_channel' => env('FIREBASE_HTTP_LOG_CHANNEL'),
                'http_debug_log_channel' => env('FIREBASE_HTTP_DEBUG_LOG_CHANNEL'),
            ],

            /*
             * ------------------------------------------------------------------------
             * HTTP Client Options
             * ------------------------------------------------------------------------
             *
             * Behavior of the HTTP Client performing the API requests
             */

            'http_client_options' => [

                /*
                 * Use a proxy that all API requests should be passed through.
                 * (default: none)
                 */

                'proxy' => env('FIREBASE_HTTP_CLIENT_PROXY'),

                /*
                 * Set the maximum amount of seconds (float) that can pass before
                 * a request is considered timed out
                 *
                 * The default time out can be reviewed at
                 * https://github.com/kreait/firebase-php/blob/6.x/src/Firebase/Http/HttpClientOptions.php
                 */

                'timeout' => env('FIREBASE_HTTP_CLIENT_TIMEOUT'),

                'guzzle_middlewares' => [],
            ],
        ],
    ],
];
