using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Text.RegularExpressions;
using System.Threading.Tasks;

namespace PHP2CSharp.Core
{
    public class PHP2CSharpConverter
    {
        private const string METHOD_FULL = @"/\*\*(.*?)\*/\s*(public|private|protected)\s*function\s*([0-9,a-z,A-Z,_]+)\((.*?)\)";
        private const string CONSTANT_STRING = @"const\s*([0-9,a-z,A-Z,_]+)\s*=\s*\""(.*?)\""\s*;";
        private const string CONSTANT_INT = @"const\s*([0-9,a-z,A-Z,_]+)\s*=\s*([0-9]+)\s*;";
        private const string REGEX_NAMESPACE = @"namespace ([a-z,A-Z,\\]+);";
        private const string PROPERTIES = @"private \$([0-9,a-z,A-Z,_]+).*;";
        private const string PROPERTY = @"private \${0}.*;";
        private const string PROPERTY_GET_DOC = @"/\*\*\s*\*\s*@return\s*([0-9,a-z,A-Z]+)\s*\*/";
        private const string PROPERTY_GET = @"public function get([0-9,a-z,A-Z,_]+)\(\)\s*{\s*return\s*\$this->{0};\s*}";
        private const string PROPERTY_SET_DOC = @"/\*\*\s*\*\s*@param\s*([0-9,a-z,A-Z]+)\s*\$([0-9,a-z,A-Z]+)\s*\*/";
        private const string PROPERTY_SET = @"public function set([0-9,a-z,A-Z,_]+)\(\$([0-9,a-z,A-Z,_]+)\)\s*{\s*\$this->{0}\s*=\s*\$([0-9,a-z,A-Z,_]+);\s*}";
        private const string REGEX_ARRAY = @"private\s*\$([0-9,a-z,A-Z,_]+)\s*=\s*array\(\);";

        private string convertMethod(string originalSource) {
            return originalSource;
        }

        private string convertArray(string originalSource)
        {
            originalSource = Regex.Replace(originalSource, REGEX_ARRAY, delegate (Match match) {
                return "private IList<object> " + match.Groups[1].Value + " = new List<object>();";
            }, RegexOptions.IgnoreCase);
            return originalSource;
        }

        private string convertConstant(string originalSource)
        {
            originalSource = Regex.Replace(originalSource, CONSTANT_STRING, delegate (Match match) {
                return "public const string " + match.Groups[1].Value + " = \"" + match.Groups[2].Value + "\";";
            }, RegexOptions.IgnoreCase);
            originalSource = Regex.Replace(originalSource, CONSTANT_INT, delegate (Match match) {
                return "public const int " + match.Groups[1].Value + " = \"" + match.Groups[2].Value + "\";";
            }, RegexOptions.IgnoreCase);
            return originalSource;
        }

        private string convertProperty(string originalSource) {
            var results = Regex.Matches(originalSource, PROPERTIES);
            foreach (Match match in results)
            {
                var prop = match.Groups[1].Value;
                var propName = prop;
                string typeName = "string";

                bool hasGet = false;
                string patternFormat = PROPERTY_GET_DOC + @"\s*" + PROPERTY_GET;
                //string pattern = string.Format(patternFormat, prop);
                string pattern = patternFormat.Replace("{0}", prop);
                var result = Regex.Match(originalSource, pattern, RegexOptions.IgnoreCase | RegexOptions.Multiline);
                if (result.Success) {
                    typeName = result.Groups[1].Value;
                    propName = result.Groups[2].Value;
                    hasGet = true;
                }
                //pattern = string.Format(PROPERTY_GET, prop);
                pattern = PROPERTY_GET.Replace("{0}", prop);
                result = Regex.Match(originalSource, pattern, RegexOptions.IgnoreCase | RegexOptions.Multiline);
                if (result.Success)
                {
                    propName = result.Groups[1].Value;
                    hasGet = true;
                }

                bool hasSet = false;
                patternFormat = PROPERTY_SET_DOC + @"\s*" + PROPERTY_SET;
                //pattern = string.Format(patternFormat, prop);
                pattern = patternFormat.Replace("{0}", prop);
                result = Regex.Match(originalSource, pattern, RegexOptions.IgnoreCase | RegexOptions.Multiline);
                if (result.Success)
                {
                    typeName = result.Groups[1].Value;
                    propName = result.Groups[3].Value;
                    hasSet = true;
                }
                //pattern = string.Format(PROPERTY_GET, prop);
                pattern = PROPERTY_SET.Replace("{0}", prop);
                result = Regex.Match(originalSource, pattern, RegexOptions.IgnoreCase | RegexOptions.Multiline);
                if (result.Success)
                {
                    propName = result.Groups[1].Value;
                    hasSet = true;
                }

                if (hasGet && hasSet) {
                    //pattern = string.Format(PROPERTY_GET, prop);
                    pattern = PROPERTY_GET.Replace("{0}", prop);
                    var propStr = string.Format("public {0} {1} {{ get; set; }}", typeName, propName);
                    originalSource = Regex.Replace(originalSource, pattern, propStr, RegexOptions.IgnoreCase | RegexOptions.Multiline);
                    pattern = PROPERTY_SET.Replace("{0}", prop);
                    originalSource = Regex.Replace(originalSource, pattern, "", RegexOptions.IgnoreCase | RegexOptions.Multiline);
                    pattern = PROPERTY.Replace("{0}", prop);
                    originalSource = Regex.Replace(originalSource, pattern, "", RegexOptions.IgnoreCase | RegexOptions.Multiline);
                }
            }
            return originalSource;
        }

        private string convertNamespace(string originalSource) {
            var source = new StringBuilder();
            source.AppendLine("using System;");
            source.AppendLine("");
            var lines = originalSource.Trim().Split(new[] { '\n' });
            bool hasNamespace = false;
            foreach (var line in lines) {
                if (string.IsNullOrEmpty(line)) {
                    continue;
                }
                var currentLine = line;
                if (hasNamespace) {
                    currentLine = "    " + currentLine;
                }
                else
                {
                    var result = Regex.Match(line, REGEX_NAMESPACE);
                    if (result.Success)
                    {
                        hasNamespace = true;
                        var ns = result.Groups[1].Value.Replace('\\', '.');
                        currentLine = "namespace " + ns + " {";
                    }
                }
                source.AppendLine(currentLine);
            }
            if (hasNamespace) {
                source.AppendLine("}");
            }
            return source.ToString();
        }

        public string convert(string originalSource) {
            string source = originalSource;
            source = Regex.Replace(source, @"\<\?php", "", RegexOptions.IgnoreCase);
            source = Regex.Replace(source, @"\?\>", "", RegexOptions.IgnoreCase);
            source = Regex.Replace(source, @"class ([0-9,a-z,A-Z,_]+)", delegate (Match match) {
                return "public class " + match.Groups[1].Value;
            }, RegexOptions.IgnoreCase);
            source = convertNamespace(source);
            source = convertConstant(source);
            source = convertProperty(source);
            source = convertArray(source);
            source = convertMethod(source);
            source = Regex.Replace(source, @"/\*\*.*?\*/", "", RegexOptions.IgnoreCase | RegexOptions.Singleline);
            return source;
        }
    }
}
