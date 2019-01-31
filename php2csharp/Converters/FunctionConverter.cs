using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Text.RegularExpressions;
using System.Threading.Tasks;

namespace PHP2CSharp.Converters
{
    public class FunctionConverter : BaseConverter
    {
        private const string IS_NULL = @"is_null\((.*?)\)";

        private IDictionary<string, string> _keywords;

        public FunctionConverter() {
            _keywords = new Dictionary<string, string>() {
                ["sprintf"] = "string.Format",
                ["array()"] = "new List<object>()",
                [".="] = "+=",
                ["%s"] = "{0}",
                ["ceil"] = "Math.Ceiling",
                ["floor"] = "Math.Floor",
            };
        }

        public override string convert(string sourceCode)
        {
            sourceCode = Regex.Replace(sourceCode, "!" + IS_NULL, delegate (Match match) {
                return match.Groups[1].Value + " != null";
            }, RegexOptions.IgnoreCase);

            sourceCode = Regex.Replace(sourceCode, IS_NULL, delegate (Match match) {
                return match.Groups[1].Value + " == null";
            }, RegexOptions.IgnoreCase);

            foreach (var keyword in _keywords) {
                sourceCode = sourceCode.Replace(keyword.Key, keyword.Value);
            }

            return sourceCode;
        }
    }
}
