# Yii Definitions Change Log

## 2.0.1 under development

- Enh #43: Add parameter `$optional` to `Reference::to()` method. Optional references returns `null` when container 
  don't have entry. (@vjik)

## 2.0.0 June 17, 2022

- New #37: Make method `DefinitionValidator::validateArrayDefinition()` public (@vjik)
- Chg #30: Rename method `ArrayDefinition::setReferenceContainer()` to `withReferenceContainer()` and make it
  immutable (@vjik)
- Chg #37: Remove method `ParameterDefinition::isBuiltin()` (@vjik)

## 1.0.2 April 01, 2022

- Bug #32: Throw exception instead of returning default value if optional dependency exists but there is an exception
  when getting it (@vjik)
- Bug #34: In one of edge cases don't throw exception if container returned result of incorrect type (@vjik)

## 1.0.1 December 19, 2021

- Bug #31: Add support for objects as default parameter values (@vjik)

## 1.0.0 November 30, 2021

- Initial release.
