<?php

namespace App\Enums;

enum CourseType: string
{
    case FaceToFace = 'face_to_face';
    case PartTime = 'part_time';
    case Online = 'online';
}
