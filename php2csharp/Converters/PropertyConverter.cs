using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Text.RegularExpressions;
using System.Threading.Tasks;

namespace PHP2CSharp.Converters
{
    public class PropertyConverter : BaseConverter
    {
        private const string PROPERTIES = @"private \$([0-9,a-z,A-Z,_]+).*;";
        private const string PROPERTY = @"private \${0}.*;";
        private const string PROPERTY_GET_DOC = @"/\*\*\s*\*\s*@return\s*([0-9,a-z,A-Z]+)\s*\*/";
        private const string PROPERTY_GET = @"public function get([0-9,a-z,A-Z,_]+)\(\)\s*{\s*return\s*\$this->{0};\s*}";
        private const string PROPERTY_SET_DOC = @"/\*\*\s*\*\s*@param\s*([0-9,a-z,A-Z]+)\s*\$([0-9,a-z,A-Z]+)\s*\*/";
        private const string PROPERTY_SET = @"public function set([0-9,a-z,A-Z,_]+)\(\$([0-9,a-z,A-Z,_]+)\)\s*{\s*\$this->{0}\s*=\s*\$([0-9,a-z,A-Z,_]+);\s*}";

        public override string convert(string sourceCode)
        {
            var results = Regex.Matches(sourceCode, PROPERTIES, RegexOptions.IgnoreCase);
            foreach (Match match in results)
            {
                var prop = match.Groups[1].Value;
                var propName = prop;
                string typeName = "string";

                bool hasGet = false;
                string patternFormat = PROPERTY_GET_DOC + @"\s*" + PROPERTY_GET;
                //string pattern = string.Format(patternFormat, prop);
                string pattern = patternFormat.Replace("{0}", prop);
                var result = Regex.Match(sourceCode, pattern, RegexOptions.IgnoreCase | RegexOptions.Multiline);
                if (result.Success)
                {
                    typeName = result.Groups[1].Value;
                    propName = result.Groups[2].Value;
                    hasGet = true;
                }
                //pattern = string.Format(PROPERTY_GET, prop);
                pattern = PROPERTY_GET.Replace("{0}", prop);
                result = Regex.Match(sourceCode, pattern, RegexOptions.IgnoreCase | RegexOptions.Multiline);
                if (result.Success)
                {
                    propName = result.Groups[1].Value;
                    hasGet = true;
                }

                bool hasSet = false;
                patternFormat = PROPERTY_SET_DOC + @"\s*" + PROPERTY_SET;
                //pattern = string.Format(patternFormat, prop);
                pattern = patternFormat.Replace("{0}", prop);
                result = Regex.Match(sourceCode, pattern, RegexOptions.IgnoreCase | RegexOptions.Multiline);
                if (result.Success)
                {
                    typeName = result.Groups[1].Value;
                    propName = result.Groups[3].Value;
                    hasSet = true;
                }
                //pattern = string.Format(PROPERTY_GET, prop);
                pattern = PROPERTY_SET.Replace("{0}", prop);
                result = Regex.Match(sourceCode, pattern, RegexOptions.IgnoreCase | RegexOptions.Multiline);
                if (result.Success)
                {
                    propName = result.Groups[1].Value;
                    hasSet = true;
                }

                if (hasGet && hasSet)
                {
                    patternFormat = PROPERTY_GET_DOC + @"\s*" + PROPERTY_GET;
                    //pattern = string.Format(PROPERTY_GET, prop);
                    pattern = patternFormat.Replace("{0}", prop);
                    var propStr = string.Format("public {0} {1} {{ get; set; }}", typeName, propName);
                    sourceCode = Regex.Replace(sourceCode, pattern, propStr, RegexOptions.IgnoreCase | RegexOptions.Multiline);

                    pattern = PROPERTY_GET.Replace("{0}", prop);
                    propStr = string.Format("public {0} {1} {{ get; set; }}", typeName, propName);
                    sourceCode = Regex.Replace(sourceCode, pattern, propStr, RegexOptions.IgnoreCase | RegexOptions.Multiline);

                    patternFormat = PROPERTY_SET_DOC + @"\s*" + PROPERTY_SET;
                    pattern = patternFormat.Replace("{0}", prop);
                    sourceCode = Regex.Replace(sourceCode, pattern, "", RegexOptions.IgnoreCase | RegexOptions.Multiline);

                    pattern = PROPERTY_SET.Replace("{0}", prop);
                    sourceCode = Regex.Replace(sourceCode, pattern, "", RegexOptions.IgnoreCase | RegexOptions.Multiline);

                    pattern = PROPERTY.Replace("{0}", prop);
                    sourceCode = Regex.Replace(sourceCode, pattern, "", RegexOptions.IgnoreCase | RegexOptions.Multiline);
                }
            }
            return sourceCode;
        }
    }
}
