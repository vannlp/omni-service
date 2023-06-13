<?php


namespace App;


class NinjaSync extends BaseModel
{
       /**
        * @var string
        */
       protected $table = 'ninja_article_interactions';

       protected $fillable = [
              "name",
              "name_post",
              "reaction",
              "comment",
              "share",
              "company_id",
              "deleted",
              "created_at",
              "created_by",
              "updated_at",
              "updated_by",
              "deleted_at",
              "deleted_by",
       ];
}
